<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\OneTimePassword;
use Illuminate\Console\Command;
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

        foreach ($affected as $user) {
            $oneTime = OneTimePassword::generate();

            $user->forceFill([
                'password' => Hash::make($oneTime),
                'default_password' => $oneTime,
                // Still not their own choice, so the forced change stands.
                'password_changed' => false,
            ])->save();

            $rows[] = [$user->email, $oneTime];
        }

        $this->info("Rotated {$affected->count()} passwords.");

        if ($csvPath) {
            // Written before anything else can read it: these are live
            // credentials, and the file exists only to be distributed and deleted.
            $handle = fopen($csvPath, 'w');
            chmod($csvPath, 0600);
            fputcsv($handle, ['email', 'one_time_password']);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);

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
