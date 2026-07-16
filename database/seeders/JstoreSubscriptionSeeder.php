<?php

namespace Database\Seeders;

use App\Models\LibrarySubscription;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * JstoreSubscriptionSeeder
 *
 * Imports existing JSTORE (Joomla online library subscription) records
 * into the Laravel library_subscriptions table.
 *
 * HOW TO USE:
 * 1. Export the JSTORE database tables from Joomla's phpMyAdmin.
 *    Typical table names:
 *      - jos_jstore_subscriptions
 *      - jos_jstore_packages
 *      - jos_jstore_users
 *
 * 2. Download the CSV / SQL export.
 *
 * 3. Replace the $records array below with the actual data
 *    (or parse a CSV file if large).
 *
 * 4. Run: php artisan db:seed --class=JstoreSubscriptionSeeder
 *
 * The stub below shows the expected data structure.
 * Fill it in once you have the Joomla database export.
 */
class JstoreSubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $records = [];
        try {
            \Illuminate\Support\Facades\DB::connection('joomla')->getPdo();

            $prefix = config('database.connections.joomla.prefix', 'josn9_');
            try {
                $rows = \Illuminate\Support\Facades\DB::connection('joomla')->select("
                    SELECT s.id as sub_id, s.user_id, u.email, s.price as amount_paid, s.created_date as start_date, s.expiry_date, s.status, p.title as plan_name
                    FROM {$prefix}jstore_subscriptions s
                    LEFT JOIN {$prefix}users u ON s.user_id = u.id
                    LEFT JOIN {$prefix}jstore_packages p ON s.package_id = p.id
                ");
            } catch (\Illuminate\Database\QueryException $e) {
                // Connection works but our guessed table names don't exist.
                // Discover what subscription-ish tables the Joomla DB actually
                // has, so the operator can report back the real names.
                $this->command->warn('Expected JSTORE tables not found under prefix "' . $prefix . '": ' . $e->getMessage());
                $this->discoverCandidateTables();

                return;
            }

            foreach ($rows as $row) {
                $planType = 'monthly';
                $name = strtolower($row->plan_name ?? '');
                if (str_contains($name, 'year') || str_contains($name, 'annual')) {
                    $planType = 'annual';
                } elseif (str_contains($name, 'life')) {
                    $planType = 'lifetime';
                }
                
                $records[] = [
                    'jstore_user_id'         => $row->user_id,
                    'jstore_subscription_id' => 'JSTORE-' . str_pad($row->sub_id, 4, '0', STR_PAD_LEFT),
                    'email'                  => $row->email,
                    'plan_type'              => $planType,
                    'plan_name'              => $row->plan_name ?? 'Digital Library Subscription',
                    'amount_paid'            => $row->amount_paid ?? 0.00,
                    'start_date'             => substr($row->start_date ?? now()->toDateString(), 0, 10),
                    'expiry_date'            => $row->expiry_date ? substr($row->expiry_date, 0, 10) : null,
                    'is_active'              => intval($row->status) === 1,
                    'payment_method'         => 'JSTORE Migrated',
                    'payment_reference'      => 'Joomla-' . $row->sub_id,
                ];
            }
        } catch (\Exception $e) {
            $this->command->warn('Could not connect to Joomla database for JSTORE: ' . $e->getMessage());
            $this->command->info('Falling back to empty/stub subscription array.');
        }

        $imported = 0;
        $skipped  = 0;

        foreach ($records as $record) {
            // Find the matching Laravel user by email
            $user = User::where('email', $record['email'])->first();

            if (! $user) {
                $this->command->warn("Skipping JSTORE record — no user found for email: {$record['email']}");
                $skipped++;
                continue;
            }

            LibrarySubscription::updateOrCreate(
                ['jstore_subscription_id' => $record['jstore_subscription_id']],
                [
                    'user_id'                => $user->id,
                    'plan_name'              => $record['plan_name'],
                    'plan_type'              => $record['plan_type'],
                    'amount_paid'            => $record['amount_paid'],
                    'start_date'             => $record['start_date'],
                    'expiry_date'            => $record['expiry_date'] ?? null,
                    'is_active'              => $record['is_active'],
                    'payment_method'         => $record['payment_method'] ?? null,
                    'payment_reference'      => $record['payment_reference'] ?? null,
                    'jstore_user_id'         => $record['jstore_user_id'],
                    'notes'                  => 'Migrated from JSTORE (Joomla)',
                ]
            );

            $imported++;
        }

        $this->command->info("JSTORE Migration: {$imported} subscriptions imported, {$skipped} skipped.");

        if (empty($records)) {
            $this->command->warn('⚠  No JSTORE records configured yet. Fill in $records in JstoreSubscriptionSeeder.php after exporting the Joomla database.');
        }
    }

    /**
     * List tables in the Joomla DB that look like they belong to a
     * subscription / membership / e-commerce extension. Run when the guessed
     * JSTORE table names miss, so the real extension's tables can be spotted
     * (J2Store, OS Membership, Akeeba Subs, PayPlans, …) and the import
     * query rewritten against them.
     */
    private function discoverCandidateTables(): void
    {
        try {
            $db = config('database.connections.joomla.database');
            $candidates = \Illuminate\Support\Facades\DB::connection('joomla')->select(
                "SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = ?
                   AND (TABLE_NAME LIKE '%jstor%' OR TABLE_NAME LIKE '%j2store%'
                     OR TABLE_NAME LIKE '%subscri%' OR TABLE_NAME LIKE '%member%'
                     OR TABLE_NAME LIKE '%akeeba%' OR TABLE_NAME LIKE '%payplan%'
                     OR TABLE_NAME LIKE '%shop%' OR TABLE_NAME LIKE '%order%')
                 ORDER BY TABLE_NAME",
                [$db]
            );

            if (! empty($candidates)) {
                $this->command->info('Subscription-like tables actually present in the Joomla DB:');
                $this->command->table(
                    ['Table', '~Rows'],
                    array_map(fn ($t) => [$t->TABLE_NAME, $t->TABLE_ROWS], $candidates)
                );
            }

            // Full component inventory: group every table by the token after the
            // Joomla prefix. An extension with an unexpected name (so none of the
            // LIKE patterns above hit it) still shows up here.
            $prefix = config('database.connections.joomla.prefix', 'josn9_');
            $components = \Illuminate\Support\Facades\DB::connection('joomla')->select(
                "SELECT SUBSTRING_INDEX(SUBSTRING(TABLE_NAME, ?), '_', 1) AS component,
                        COUNT(*) AS tbl_count,
                        COALESCE(SUM(TABLE_ROWS), 0) AS total_rows
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME LIKE ?
                 GROUP BY component
                 ORDER BY total_rows DESC, component",
                [strlen($prefix) + 1, $db, $prefix.'%']
            );

            $this->command->info('Component inventory (every extension with tables in this DB):');
            $this->command->table(
                ['Component', 'Tables', '~Total rows'],
                array_map(fn ($c) => [$c->component, $c->tbl_count, $c->total_rows], $components)
            );
            $this->command->info('→ If no commerce/subscription component appears with data, the JSTORE subscriptions were never stored in this Joomla DB — fill $records manually (or import from CSV) instead.');
        } catch (\Throwable $e) {
            $this->command->warn('Could not inspect information_schema: ' . $e->getMessage());
        }
    }
}
