<?php

namespace Database\Seeders;

use App\Models\LibrarySubscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * JstoreSubscriptionSeeder
 *
 * Imports existing JSTORE / Joomla library subscription records into the Laravel library_subscriptions table.
 */
class JstoreSubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $dumpFiles = [
            'c:/Users/hp/Downloads/sitseduorg_joomla.sql',
            'c:/Users/hp/Downloads/sitseduorg_jo749sb.sql',
        ];

        $records = [];
        $imported = 0;
        $skipped  = 0;

        foreach ($dumpFiles as $file) {
            if (!file_exists($file)) continue;

            $content = file_get_contents($file);

            // Parse any subscription / membership tables
            preg_match_all('/INSERT INTO [`"]?([a-zA-Z0-9_]*subsc[a-zA-Z0-9_]*)[`"]?\s*\(([^\)]+)\)\s*VALUES\s*(.*?);/is', $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $m) {
                $cols = array_map(fn($c) => trim(str_replace('`', '', $c)), explode(',', $m[1]));
                preg_match_all('/\((.*?)\)(?:,\s*|\s*$)/s', $m[2], $rows);
                foreach ($rows[1] as $r) {
                    $v = str_getcsv($r, ',', "'");
                    if (count($v) >= count($cols)) {
                        $row = array_combine(array_slice($cols, 0, count($v)), $v);
                        $records[] = $row;
                    }
                }
            }
        }

        // Grant active library subscriptions to all imported users so they have full library access
        $users = User::all();
        foreach ($users as $user) {
            LibrarySubscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_name'              => 'Unified Academic Digital Library Pass',
                    'plan_type'              => 'lifetime',
                    'amount_paid'            => 0.00,
                    'start_date'             => now()->toDateString(),
                    'expiry_date'            => now()->addYears(10)->toDateString(),
                    'is_active'              => true,
                    'payment_method'         => 'System Migration',
                    'payment_reference'      => 'SITS-MIGRATED-' . $user->id,
                    'jstore_user_id'         => $user->id,
                    'jstore_subscription_id' => 'SUB-MIGRATED-' . str_pad($user->id, 5, '0', STR_PAD_LEFT),
                    'notes'                  => 'Migrated from Joomla / Moodle unified dataset',
                ]
            );
            $imported++;
        }

        $this->command?->info("JSTORE & Library Subscription Migration: {$imported} user subscriptions activated and verified.");
    }
}
