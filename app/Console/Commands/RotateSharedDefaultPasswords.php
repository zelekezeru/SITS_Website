<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\OneTimePassword;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Replaces the one shared default password with a unique one per account.
 *
 * The Moodle importer originally gave every account it created the same
 * password, hardcoded in the source and therefore known to anyone with repo
 * access. This finds the accounts still sitting on that value and gives each its
 * own, so no single string opens more than one account.
 *
 * Accounts are matched by hashing, not by the stored `default_password` field —
 * that column is encrypted and rows written under an older APP_KEY can no longer
 * be decrypted, so it cannot be trusted to identify them.
 */
class RotateSharedDefaultPasswords extends Command
{
    protected $signature = 'users:rotate-shared-defaults
                            {--shared= : The shared password to look for; defaults to the importer\'s old constant}
                            {--dry-run : List affected accounts without changing anything}
                            {--csv= : Write email,password pairs here for distribution (file is created 0600)}';

    protected $description = 'Give each account still on the shared default password its own one-time password';

    public function handle(): int
    {
        $shared = (string) ($this->option('shared') ?: OneTimePassword::legacySharedDefault());
        $dryRun = (bool) $this->option('dry-run');
        $csvPath = $this->option('csv');

        // Only accounts that have never set their own password can be on the
        // shared default; checking the hash of everyone else would be wasted
        // work and would risk rotating a password somebody actually chose.
        $candidates = User::where('password_changed', false)->get();

        $this->info("Scanning {$candidates->count()} accounts that have never changed their password...");

        $affected = $candidates->filter(fn (User $u) => Hash::check($shared, $u->password));

        if ($affected->isEmpty()) {
            $this->info('No account is using the shared default. Nothing to do.');

            return self::SUCCESS;
        }

        $this->warn("{$affected->count()} accounts share one password. Each will get its own.");

        if ($dryRun) {
            $this->table(['id', 'email'], $affected->map(fn ($u) => [$u->id, $u->email])->all());
            $this->info('Dry run — nothing was changed.');

            return self::SUCCESS;
        }

        $rows = [];
        $failed = [];

        foreach ($affected as $user) {
            $oneTime = OneTimePassword::generate();

            try {
                // Written through the query builder, not the model, on purpose.
                // Saving via Eloquent makes it decrypt the PREVIOUS
                // default_password to diff it, and rows encrypted under an older
                // APP_KEY throw "The MAC is invalid" — which aborts the whole
                // run partway, leaving some accounts rotated and unrecorded.
                // Encrypting by hand sidesteps reading the old value at all.
                DB::table($user->getTable())->where('id', $user->getKey())->update([
                    'password' => Hash::make($oneTime),
                    'default_password' => Crypt::encryptString($oneTime),
                    // Still not their own choice, so the forced change stands.
                    'password_changed' => false,
                ]);

                $rows[] = [$user->email, $oneTime];
            } catch (\Throwable $e) {
                // One unwritable row must not cost everyone else their rotation.
                $failed[] = [$user->email, $e->getMessage()];
            }
        }

        $this->info('Rotated '.count($rows).' passwords.');

        if ($failed !== []) {
            $this->newLine();
            $this->error(count($failed).' accounts could not be rotated:');
            $this->table(['email', 'error'], $failed);
        }

        if ($csvPath) {
            // Export every account still awaiting its first login, not just the
            // ones rotated in this run: an earlier interrupted run may have
            // issued passwords that were never written down, and those exist
            // nowhere but the database.
            $pending = User::where('password_changed', false)->get()
                ->map(fn (User $u) => [$u->email, $u->readableDefaultPassword()])
                ->filter(fn (array $r) => $r[1] !== null)
                ->values();

            // Written before anything else can read it: these are live
            // credentials, and the file exists only to be distributed and deleted.
            $handle = fopen($csvPath, 'w');
            chmod($csvPath, 0600);
            fputcsv($handle, ['email', 'one_time_password']);
            foreach ($pending as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);

            $this->newLine();
            $this->info("Exported {$pending->count()} pending one-time passwords.");

            $this->newLine();
            $this->warn("Credentials written to {$csvPath} (mode 0600).");
            $this->warn('Distribute them, then DELETE that file — it is a list of working passwords.');
        } else {
            $this->newLine();
            $this->line('No --csv given. Read each password back per user from the admin UI,');
            $this->line('or re-run with --csv=/path/outside/public_html/passwords.csv to export them.');
        }

        return self::SUCCESS;
    }
}
