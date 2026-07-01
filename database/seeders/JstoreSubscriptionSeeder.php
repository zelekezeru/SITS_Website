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
            $rows = \Illuminate\Support\Facades\DB::connection('joomla')->select("
                SELECT s.id as sub_id, s.user_id, u.email, s.price as amount_paid, s.created_date as start_date, s.expiry_date, s.status, p.title as plan_name
                FROM {$prefix}jstore_subscriptions s
                LEFT JOIN {$prefix}users u ON s.user_id = u.id
                LEFT JOIN {$prefix}jstore_packages p ON s.package_id = p.id
            ");
            
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
}
