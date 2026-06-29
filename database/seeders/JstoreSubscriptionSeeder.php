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
        // ── STUB DATA ─────────────────────────────────────────────────────────
        // Replace with actual exported JSTORE records.
        // Each record should have:
        //   jstore_user_id     → the Joomla user_id from jos_users
        //   jstore_sub_id      → the JSTORE subscription ID
        //   email              → used to match the Laravel user
        //   plan_type          → monthly | annual | lifetime
        //   plan_name          → human-readable plan name
        //   amount_paid        → original amount in ETB
        //   start_date         → subscription start (Y-m-d)
        //   expiry_date        → null for lifetime, date otherwise
        //   is_active          → 1 or 0

        $records = [
            // Example — replace with real JSTORE data:
            // [
            //     'jstore_user_id'        => 42,
            //     'jstore_subscription_id' => 'JSTORE-0001',
            //     'email'                  => 'student@example.com',
            //     'plan_type'              => 'annual',
            //     'plan_name'              => 'Annual Access',
            //     'amount_paid'            => 1200.00,
            //     'start_date'             => '2025-01-01',
            //     'expiry_date'            => '2026-01-01',
            //     'is_active'              => true,
            //     'payment_method'         => 'CBE',
            //     'payment_reference'      => 'CBE-TRN-XXXX',
            // ],
        ];

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
}
