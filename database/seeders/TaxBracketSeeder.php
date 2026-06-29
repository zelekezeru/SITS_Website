<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxBracketSeeder extends Seeder
{
    /**
     * Ethiopian Personal Income Tax — Income Tax (Amendment) Proclamation No. 1395/2025,
     * effective July 2025. Six monthly bands. tax = income * rate - deduction.
     */
    public function run(): void
    {
        $effective = '2025-07-07';

        $brackets = [
            ['min_income' => 0,       'max_income' => 2000,  'rate' => 0,  'deduction' => 0],
            ['min_income' => 2000.01, 'max_income' => 4000,  'rate' => 15, 'deduction' => 300],
            ['min_income' => 4000.01, 'max_income' => 7000,  'rate' => 20, 'deduction' => 500],
            ['min_income' => 7000.01, 'max_income' => 10000, 'rate' => 25, 'deduction' => 850],
            ['min_income' => 10000.01,'max_income' => 14000, 'rate' => 30, 'deduction' => 1350],
            ['min_income' => 14000.01,'max_income' => null,  'rate' => 35, 'deduction' => 2050],
        ];

        foreach ($brackets as $b) {
            DB::table('tax_brackets')->updateOrInsert(
                ['min_income' => $b['min_income'], 'effective_from' => $effective],
                array_merge($b, [
                    'is_active' => true,
                    'effective_from' => $effective,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
