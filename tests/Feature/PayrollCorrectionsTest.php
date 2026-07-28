<?php

namespace Tests\Feature;

use App\Enums\AttendanceRowMatchStatus;
use App\Models\AttendanceImport;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollComponentAssignment;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\User;
use App\Services\Attendance\AttendanceImportService;
use App\Services\Payroll\PayrollRunService;
use App\Services\PayrollCalculator;
use App\Support\PayrollSheetPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regressions for defects found auditing the payroll pipeline: overtime never
 * reaching the calculator, the split pension-tax default, a recompute retracting
 * a submission, the orphaned Kircha column, and the sheet's absent-day drift.
 */
class PayrollCorrectionsTest extends TestCase
{
    use RefreshDatabase;

    private function employee(array $attributes = []): Employee
    {
        $user = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);

        return Employee::create($attributes + [
            'user_id' => $user->id,
            'staff_no' => 'EMP-'.$user->id,
            'full_name_en' => 'Test Employee '.$user->id,
            'base_salary' => 10000,
            'legal_daily_hour_limit' => 8,
            'is_active' => true,
            'hired_at' => '2024-01-01',
        ]);
    }

    private function period(string $status = 'open'): PayrollPeriod
    {
        return PayrollPeriod::create([
            'name' => 'August 2026',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => $status,
        ]);
    }

    // ── Overtime plumbing ────────────────────────────────────────────────────

    /** Approving an import must carry the device's overtime minutes into payroll. */
    public function test_import_approval_carries_overtime_into_the_attendance_record(): void
    {
        $period = $this->period();
        $employee = $this->employee();
        $reviewer = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);

        $import = AttendanceImport::create([
            'payroll_period_id' => $period->id,
            'original_filename' => 'august.xlsx',
            'file_path' => 'imports/august.xlsx',
            'status' => 'pending_review',
        ]);

        $import->rows()->create([
            'device_employee_code' => '1001',
            'device_name' => 'Test Employee',
            'work_duration_actual_minutes' => 10400,
            'late_minutes' => 0,
            'absent_days' => 0,
            'overtime_normal_minutes' => 300,   // 5.0 h ordinary
            'overtime_special_minutes' => 120,  // 2.0 h rest day
            'employee_id' => $employee->id,
            'match_status' => AttendanceRowMatchStatus::Matched,
            'suggested_permitted_days' => 0,
        ]);

        app(AttendanceImportService::class)->approve($import->fresh('rows'), $reviewer);

        $record = AttendanceRecord::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals(5.0, (float) $record->overtime_normal);
        $this->assertEquals(2.0, (float) $record->ot_rest);
    }

    /** And that overtime must then actually be paid. */
    public function test_overtime_hours_produce_overtime_pay(): void
    {
        $employee = $this->employee(['base_salary' => 10400]); // 400/day, 50/hour at 26×8
        $attendance = new AttendanceRecord([
            'absent_days' => 0, 'permitted_days' => 0,
            'overtime_normal' => 4, 'ot_rest' => 2,
        ]);

        $result = (new PayrollCalculator(['working_days' => 26]))->compute($employee, $attendance);

        // 4h × 50 × 1.5 = 300, plus 2h × 50 × 2.0 = 200.
        $this->assertEquals(500.0, $result['overtime']);
        $this->assertEquals(10900.0, $result['gross']);
    }

    // ── Pension tax treatment ────────────────────────────────────────────────

    /**
     * A calculator built without an explicit policy must match the seeded setting
     * (`pension_pre_tax = false`), not the opposite convention.
     */
    public function test_pension_defaults_to_post_tax_matching_the_seeded_setting(): void
    {
        $pension = new PayrollComponent([
            'name' => 'Pension (Employee 7%)',
            'kind' => 'statutory', 'calc_type' => 'percent', 'rate' => 7,
            'side' => 'employee', 'applies_to' => 'pension_members',
            'sheet_column' => 'employee_pension', 'is_active' => true,
        ]);

        $result = (new PayrollCalculator(['working_days' => 26]))
            ->compute($this->employee(), null, null, [], [$pension]);

        // Pension is taxed: the full basic salary stays in the tax base.
        $this->assertEquals(10000.0, $result['taxable_income']);
        $this->assertEquals(700.0, $result['employee_pension']);
    }

    // ── Recompute must not retract a submission ──────────────────────────────

    public function test_recomputing_a_submitted_period_keeps_it_pending_approval(): void
    {
        $period = $this->period('pending_approval');
        $this->employee();

        (new PayrollRunService())->run($period);

        $this->assertEquals('pending_approval', $period->fresh()->status->value);
    }

    public function test_recomputing_an_open_period_advances_it_to_processing(): void
    {
        $period = $this->period('open');
        $this->employee();

        (new PayrollRunService())->run($period);

        $this->assertEquals('processing', $period->fresh()->status->value);
    }

    // ── Kircha reaches its own column and the total ──────────────────────────

    public function test_kircha_deduction_lands_in_its_column_and_the_total(): void
    {
        $period = $this->period();
        $employee = $this->employee();

        $kircha = PayrollComponent::create([
            'name' => 'Kircha (Meat Share)',
            'kind' => 'deduction', 'calc_type' => 'fixed',
            'applies_to' => 'all', 'sheet_column' => 'kircha_deduction', 'is_active' => true,
        ]);

        PayrollComponentAssignment::create([
            'employee_id' => $employee->id,
            'payroll_component_id' => $kircha->id,
            'amount' => 250,
            'schedule_type' => 'monthly',
        ]);

        (new PayrollRunService())->run($period);

        $payslip = Payslip::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals(250.0, (float) $payslip->kircha_deduction);

        // And it is withheld, not merely displayed.
        $this->assertEquals(
            round((float) $payslip->gross - (float) $payslip->total_deductions, 2),
            round((float) $payslip->net_pay, 2),
        );

        // The sheet folds it into "Other Ded." alongside other_deduction.
        $rows = PayrollSheetPresenter::rows($period);
        $this->assertEquals(250.0, $rows[0]['other_deduction']);
    }

    // ── Absent days no longer drift from the amount charged ──────────────────

    /**
     * The sheet used to recompute absent days live from attendance, so editing
     * attendance after a run left the day count disagreeing with the money.
     */
    public function test_sheet_absent_days_come_from_the_run_not_live_attendance(): void
    {
        $period = $this->period();
        $employee = $this->employee();

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'absent_days' => 3,
            'permitted_days' => 1,
            'status' => 'verified',
        ]);

        (new PayrollRunService())->run($period);

        $payslip = Payslip::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals(2, $payslip->absent_days);

        // Attendance is corrected afterwards, without a recompute.
        AttendanceRecord::where('employee_id', $employee->id)->update(['absent_days' => 9]);

        $rows = PayrollSheetPresenter::rows($period);
        $this->assertEquals(2, $rows[0]['absent_days'], 'the sheet must report the days the run charged');
        $this->assertEquals(
            round(2 * (10000 / 26), 2),
            round($rows[0]['absence_deduction'], 2),
        );
    }

    /**
     * The absence amount is stored on the payslip, not only reconstructed from the
     * "Unpaid Absence" line. Without `absence_deduction` in $fillable the run would
     * silently drop it and the column would sit at zero.
     */
    public function test_absence_deduction_is_stored_on_the_payslip(): void
    {
        $period = $this->period();
        $employee = $this->employee();

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'absent_days' => 2,
            'permitted_days' => 0,
            'status' => 'verified',
        ]);

        (new PayrollRunService())->run($period);

        $payslip = Payslip::where('employee_id', $employee->id)->firstOrFail();
        $expected = round(2 * (10000 / 26), 2);

        $this->assertEquals($expected, round((float) $payslip->absence_deduction, 2));

        // And it agrees with the itemised line it is derived from.
        $line = $payslip->lines->first(fn ($l) => str_starts_with($l->label, 'Unpaid Absence'));
        $this->assertNotNull($line);
        $this->assertEquals($expected, round((float) $line->amount, 2));
    }

    /** No absence means a zero column, and the sheet hides it. */
    public function test_absence_columns_are_hidden_when_nobody_is_absent(): void
    {
        $period = $this->period();
        $this->employee();

        (new PayrollRunService())->run($period);

        $rows = PayrollSheetPresenter::rows($period);
        $columns = PayrollSheetPresenter::activeColumns($rows);

        $this->assertEquals(0.0, $rows[0]['absence_deduction']);
        $this->assertArrayNotHasKey('absence_deduction', $columns);
        $this->assertArrayNotHasKey('absent_days', $columns);
    }

    /** With absence present, both columns surface on the sheet. */
    public function test_absence_columns_appear_once_someone_is_absent(): void
    {
        $period = $this->period();
        $employee = $this->employee();

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'absent_days' => 1,
            'permitted_days' => 0,
            'status' => 'verified',
        ]);

        (new PayrollRunService())->run($period);

        $columns = PayrollSheetPresenter::activeColumns(PayrollSheetPresenter::rows($period));

        $this->assertArrayHasKey('absent_days', $columns);
        $this->assertArrayHasKey('absence_deduction', $columns);
    }
}
