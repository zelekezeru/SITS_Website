<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Kpi;
use App\Models\EvaluationPeriod;
use App\Models\Evaluation;
use App\Models\EvaluationRating;
use App\Models\NarrativeReport;
use App\Models\User;
use App\Enums\Cadence;
use App\Enums\EvaluationPeriodStatus;
use App\Enums\EvaluationStatus;
use App\Enums\KpiStatus;
use App\Enums\MeasureType;
use App\Enums\RaterType;
use App\Enums\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvaluationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get default users for approved/confirmed blaming
        $adminUser = User::role('President / Super Admin')->first() ?? User::first();
        if (!$adminUser) {
            return;
        }

        // 2. Create an open evaluation period
        $period = EvaluationPeriod::firstOrCreate(
            ['name' => '2026 First Quarter (Q1)'],
            [
                'cadence'         => Cadence::Quarterly,
                'start_date'      => '2026-01-01',
                'end_date'        => '2026-03-31',
                'status'          => EvaluationPeriodStatus::Open,
                'formula_version' => '1.0',
            ]
        );

        // 3. Create realistic KPIs
        $kpi1 = Kpi::firstOrCreate(
            ['title_en' => 'Satellite Campus Operational Efficiency'],
            [
                'title_am'     => 'የሳተላይት ግቢ የስራ ክንውን ብቃት',
                'measure_type' => MeasureType::Quantitative,
                'target_value' => 100.00,
                'unit'         => '%',
                'weight'       => 40.00,
                'status'       => KpiStatus::Created,
                'approved_by'  => $adminUser->id,
                'confirmed_by' => $adminUser->id,
                'created_by'   => $adminUser->id,
            ]
        );

        $kpi2 = Kpi::firstOrCreate(
            ['title_en' => 'Curriculum Delivery Rate'],
            [
                'title_am'     => 'የስርዓተ-ትምህርት አቅርቦት ፍጥነት',
                'measure_type' => MeasureType::Quantitative,
                'target_value' => 100.00,
                'unit'         => '%',
                'weight'       => 30.00,
                'status'       => KpiStatus::Created,
                'approved_by'  => $adminUser->id,
                'confirmed_by' => $adminUser->id,
                'created_by'   => $adminUser->id,
            ]
        );

        $kpi3 = Kpi::firstOrCreate(
            ['title_en' => 'Administrative Resource Management'],
            [
                'title_am'     => 'የአስተዳደር ሃብት አስተዳደር',
                'measure_type' => MeasureType::Quantitative,
                'target_value' => 95.00,
                'unit'         => '%',
                'weight'       => 30.00,
                'status'       => KpiStatus::Created,
                'approved_by'  => $adminUser->id,
                'confirmed_by' => $adminUser->id,
                'created_by'   => $adminUser->id,
            ]
        );

        // 4. Assign KPIs and create Evaluations for employees
        $employees = Employee::all();
        foreach ($employees as $employee) {
            // Assign KPIs
            $employee->kpis()->syncWithoutDetaching([$kpi1->id, $kpi2->id, $kpi3->id]);

            // Create Evaluation
            $evaluation = Evaluation::firstOrCreate(
                [
                    'employee_id'          => $employee->id,
                    'evaluation_period_id' => $period->id,
                ],
                [
                    'auto_score'      => 85.00,
                    'manager_score'   => 88.00,
                    'executive_score' => 90.00,
                    'final_score'     => 88.50,
                    'status'          => EvaluationStatus::Draft,
                ]
            );

            // Create some rating records for each KPI
            EvaluationRating::firstOrCreate(
                [
                    'evaluation_id' => $evaluation->id,
                    'kpi_id'        => $kpi1->id,
                    'rater_user_id' => $adminUser->id,
                ],
                [
                    'rater_type'    => RaterType::Manager,
                    'score'         => 88.00,
                    'comment_en'    => 'Strong delivery on the main campus satellite centers, meeting all expectations.',
                    'comment_am'    => 'በዋናው ግቢ የሳተላይት ማዕከላት ላይ ጠንካራ አቅርቦት፣ ሁሉንም የሚጠበቁ ነገሮች ያሟላል።',
                ]
            );

            EvaluationRating::firstOrCreate(
                [
                    'evaluation_id' => $evaluation->id,
                    'kpi_id'        => $kpi2->id,
                    'rater_user_id' => $adminUser->id,
                ],
                [
                    'rater_type'    => RaterType::Manager,
                    'score'         => 90.00,
                    'comment_en'    => 'Outstanding curriculum completion and scheduling alignment.',
                    'comment_am'    => 'የላቀ የስርዓተ-ትምህርት አጠናቀቅ እና የጊዜ ሰሌዳ አሰላለፍ።',
                ]
            );
        }

        // 5. Seed a NarrativeReport for Pr. Tesfaye Gebre
        $tesfaye = Employee::where('full_name_en', 'Pr. Tesfaye Gebre')->first();
        if ($tesfaye) {
            NarrativeReport::firstOrCreate(
                [
                    'employee_id'          => $tesfaye->id,
                    'evaluation_period_id' => $period->id,
                ],
                [
                    'language' => Language::En,
                    'body'     => 'During the first quarter of 2026, I managed the integration of our new Satellite & Learning Sites. All primary curriculum modules were successfully delivered on schedule. The team achieved a 92% average rating across campus reviews. A minor operational constraint was experienced in coordination with regional logistical suppliers, which delayed materials by two days, but the team compensated via remote modules. In Q2, we aim to extend these remote tracking dashboards to further reduce material delivery latency.',
                ]
            );
        }
    }
}
