<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StrategicPlanSeeder extends Seeder
{
    /** Active year FY2026 and the five SITS strategic pillars. */
    public function run(): void
    {
        DB::table('years')->updateOrInsert(
            ['label' => 'FY2026'],
            [
                'label' => 'FY2026',
                'start_date' => '2025-07-01',
                'end_date' => '2026-06-30',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $yearId = DB::table('years')->where('label', 'FY2026')->value('id');

        $pillars = [
            ['pillar' => 'program_delivery',        'name' => 'Program Delivery'],
            ['pillar' => 'enrollment',              'name' => 'Enrollment Growth'],
            ['pillar' => 'finance',                 'name' => 'Financial Health'],
            ['pillar' => 'organizational_capacity', 'name' => 'Organizational Capacity'],
            ['pillar' => 'governance',              'name' => 'Governance'],
        ];

        foreach ($pillars as $p) {
            DB::table('strategies')->updateOrInsert(
                ['year_id' => $yearId, 'pillar' => $p['pillar']],
                array_merge($p, ['year_id' => $yearId, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
