<?php

namespace Database\Seeders;

use App\Models\Year;
use App\Models\Quarter;
use App\Models\Fortnight;
use App\Models\Day;
use App\Models\EvaluationPeriod;
use App\Models\PayrollPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        $year = Year::active() ?? Year::first();
        
        if (!$year) {
            // Create a default year if none exists
            $year = Year::create([
                'label' => 'FY 2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'active' => true,
            ]);
        }

        DB::transaction(function () use ($year) {
            $start = Carbon::parse($year->start_date);
            $end = Carbon::parse($year->end_date);

            // Generate Quarters & Fortnights if they don't exist
            if ($year->quarters()->count() === 0) {
                for ($q = 1; $q <= 4; $q++) {
                    $qStart = $start->copy()->addMonths(($q - 1) * 3);
                    $qEnd = $start->copy()->addMonths($q * 3)->subDay();

                    if ($q === 4 || $qEnd->gt($end)) {
                        $qEnd = $end->copy();
                    }

                    $quarter = Quarter::create([
                        'year_id' => $year->id,
                        'name' => "Q{$q}",
                        'start_date' => $qStart->toDateString(),
                        'end_date' => $qEnd->toDateString(),
                    ]);

                    $fStart = $qStart->copy();
                    $fIndex = 1;

                    while ($fStart->lt($qEnd)) {
                        $fEnd = $fStart->copy()->addDays(13);
                        if ($fEnd->gt($qEnd)) {
                            $fEnd = $qEnd->copy();
                        }

                        $fortnight = Fortnight::create([
                            'quarter_id' => $quarter->id,
                            'name' => "Q{$q}-F{$fIndex}",
                            'start_date' => $fStart->toDateString(),
                            'end_date' => $fEnd->toDateString(),
                        ]);

                        $dayDate = $fStart->copy();
                        while ($dayDate->lte($fEnd)) {
                            Day::updateOrCreate(
                                ['date' => $dayDate->copy()->startOfDay()],
                                ['fortnight_id' => $fortnight->id]
                            );
                            $dayDate->addDay();
                        }

                        $fStart->addDays(14);
                        $fIndex++;
                    }
                }
            }

            // Generate 12 months starting from fiscal year's start date
            for ($i = 0; $i < 12; $i++) {
                $currentMonth = $start->copy()->addMonths($i);
                $monthStart = $currentMonth->copy()->startOfMonth();
                $monthEnd = $currentMonth->copy()->endOfMonth();
                $name = $monthStart->format('F Y');

                // Create EvaluationPeriod
                EvaluationPeriod::firstOrCreate([
                    'name' => $name,
                ], [
                    'cadence' => 'monthly',
                    'start_date' => $monthStart->toDateString(),
                    'end_date' => $monthEnd->toDateString(),
                    'status' => 'open',
                ]);

                // Create PayrollPeriod
                PayrollPeriod::firstOrCreate([
                    'name' => $name,
                ], [
                    'start_date' => $monthStart->toDateString(),
                    'end_date' => $monthEnd->toDateString(),
                    'status' => 'open',
                    'payment_date' => $monthEnd->toDateString(),
                ]);
            }

            // Generate fortnightly evaluation periods (performance cadence only).
            // Payroll runs on a monthly basis, so no fortnightly payroll periods.
            $fortnights = Fortnight::whereIn('quarter_id', $year->quarters()->pluck('id'))->get();
            foreach ($fortnights as $fortnight) {
                EvaluationPeriod::firstOrCreate([
                    'name' => $fortnight->name,
                ], [
                    'cadence' => 'fortnightly',
                    'start_date' => $fortnight->start_date->toDateString(),
                    'end_date' => $fortnight->end_date->toDateString(),
                    'status' => 'open',
                ]);
            }
        });
    }
}
