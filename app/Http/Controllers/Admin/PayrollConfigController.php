<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ComponentSide;
use App\Enums\PayrollComponentCalc;
use App\Enums\PayrollComponentKind;
use App\Enums\ScheduleType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollComponentAssignment;
use App\Models\PayrollPeriod;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin-only configuration of the payroll component registry: allowance and
 * deduction titles and the statutory pension / provident-fund rates. Seeded
 * `is_system` rows can be renamed/re-rated/deactivated but not deleted, and
 * their kind is fixed.
 *
 * Also backs the "Payroll Config" screen, which pairs the institution-wide
 * policy constants (absence, overtime, tax treatment) with each employee's
 * payroll profile — scheme membership, exemptions and standing allowance /
 * deduction assignments.
 */
class PayrollConfigController extends Controller
{
    private const SHEET_COLUMNS = [
        'allowance' => ['transport_allowance', 'housing_allowance', 'mobile_allowance', 'cash_allowance'],
        'deduction' => ['salary_advance', 'other_deduction'],
        'statutory' => ['employee_pension', 'employer_pension', 'provident_fund_employee', 'provident_fund_employer'],
    ];

    /**
     * The policy constants surfaced on the Payroll Config screen, grouped into the
     * tabs they appear under. `type` drives both casting and the rendered control;
     * `rule` is the validation applied on save. Every key must exist in `settings`.
     */
    private const POLICY = [
        'absence' => [
            ['key' => 'absence_deduction_enabled', 'type' => 'boolean', 'label' => 'Deduct for unpermitted absence',
                'help' => 'Master switch. When off, absent days are recorded but never cost pay.', 'rule' => 'boolean'],
            ['key' => 'absence_deduction_basis', 'type' => 'choice', 'label' => 'Daily-rate basis',
                'help' => 'Basic salary only, or basic plus allowances — the larger basis makes each absent day cost more.',
                'rule' => 'in:base,gross',
                'choices' => [
                    ['value' => 'base', 'label' => 'Basic salary only'],
                    ['value' => 'gross', 'label' => 'Basic + allowances'],
                ]],
            ['key' => 'absence_deduction_rate', 'type' => 'decimal', 'label' => 'Days withheld per absent day',
                'help' => '1.00 costs one day of pay per absent day. Above 1.00 is a penalty rate.', 'rule' => 'numeric|min:0|max:5'],
            ['key' => 'absence_grace_days', 'type' => 'decimal', 'label' => 'Grace days per month',
                'help' => 'Unpermitted absent days forgiven each month before any deduction applies.', 'rule' => 'numeric|min:0|max:31'],
            ['key' => 'working_days_per_month', 'type' => 'integer', 'label' => 'Working days per month',
                'help' => 'Divisor for the daily rate, and therefore for absence and overtime alike.', 'rule' => 'integer|min:1|max:31'],
        ],
        'deductions' => [
            ['key' => 'pension_pre_tax', 'type' => 'boolean', 'label' => 'Employee pension is pre-tax',
                'help' => 'On = statutory Ethiopian treatment (pension reduces taxable income). Off = the legacy SITS sheet convention.', 'rule' => 'boolean'],
            ['key' => 'transport_allowance_limit', 'type' => 'decimal', 'label' => 'Transport tax-exempt cap (ETB)',
                'help' => 'Transport allowance is tax-free up to this amount or 25% of basic salary, whichever is lower.', 'rule' => 'numeric|min:0'],
            ['key' => 'food_allowance_limit', 'type' => 'decimal', 'label' => 'Food tax-exempt cap (ETB)',
                'help' => 'Reserved for a future food-allowance component; not applied to any current component.', 'rule' => 'numeric|min:0'],
        ],
        'overtime' => [
            ['key' => 'ot_normal_multiplier', 'type' => 'decimal', 'label' => 'Ordinary overtime ×',
                'help' => 'Applied to the hourly rate for overtime on a normal working day.', 'rule' => 'numeric|min:1|max:5'],
            ['key' => 'ot_night_multiplier', 'type' => 'decimal', 'label' => 'Night premium ×',
                'help' => 'Hours worked during the statutory night window.', 'rule' => 'numeric|min:1|max:5'],
            ['key' => 'ot_rest_multiplier', 'type' => 'decimal', 'label' => 'Weekly rest day ×',
                'help' => 'Hours worked on the employee\'s weekly rest day.', 'rule' => 'numeric|min:1|max:5'],
            ['key' => 'ot_holiday_multiplier', 'type' => 'decimal', 'label' => 'Public holiday ×',
                'help' => 'Hours worked on a public holiday or institutional closed day.', 'rule' => 'numeric|min:1|max:5'],
        ],
    ];

    /** Form metadata shared with the config page (rendered by ModuleController). */
    public static function meta(): array
    {
        return [
            'kinds' => self::options(PayrollComponentKind::cases()),
            'calcTypes' => self::options(PayrollComponentCalc::cases()),
            'sides' => self::options(ComponentSide::cases()),
            'appliesTo' => [
                ['value' => 'all', 'label' => 'All employees'],
                ['value' => 'pension_members', 'label' => 'Pension scheme members'],
                ['value' => 'pf_members', 'label' => 'Provident Fund members'],
            ],
            'sheetColumns' => self::SHEET_COLUMNS,
        ];
    }

    /**
     * Payload for the Payroll Config screen: policy constants with their live
     * values, the assignable component catalogue, and every employee's payroll
     * profile with their standing assignments.
     *
     * @return array<string, mixed>
     */
    public static function pageProps(): array
    {
        $components = PayrollComponent::active()
            ->whereIn('kind', [PayrollComponentKind::Allowance, PayrollComponentKind::Deduction])
            ->orderBy('kind')->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'kind', 'calc_type', 'rate', 'taxable', 'exempt_capped', 'sheet_column']);

        $employees = Employee::query()
            ->where('is_active', true)
            ->with([
                'department:id,name_en',
                'position:id,title_en',
                'componentAssignments' => fn ($q) => $q->where('is_active', true),
                'componentAssignments.component:id,name,kind',
                'componentAssignments.startPeriod:id,name',
                'componentAssignments.endPeriod:id,name',
            ])
            ->orderBy('full_name_en')
            ->get();

        return [
            'policy' => self::policyValues(),
            'components' => $components->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'kind' => $c->kind->value,
                'is_earning' => $c->kind === PayrollComponentKind::Allowance,
                'calc_type' => $c->calc_type->value,
                'rate' => (float) $c->rate,
                'taxable' => (bool) $c->taxable,
                'exempt_capped' => (bool) $c->exempt_capped,
            ]),
            'employees' => $employees->map(fn (Employee $e) => [
                'id' => $e->id,
                'name' => $e->full_name_en,
                'staff_no' => $e->staff_no,
                'grade' => $e->grade,
                'position' => $e->position?->title_en,
                'department' => $e->department?->name_en,
                'employment_type' => $e->employment_type?->value,
                'base_salary' => (float) $e->base_salary,
                'legal_daily_hour_limit' => (int) $e->legal_daily_hour_limit,
                'has_provident_fund' => (bool) $e->has_provident_fund,
                'statutory_exempt' => (bool) $e->statutory_exempt,
                'attendance_exempt' => (bool) $e->attendance_exempt,
                'attendance_exempt_reason' => $e->attendance_exempt_reason,
                // Part-time and contract staff never contribute, whatever the flags say.
                'scheme_excluded_by_type' => in_array($e->employment_type?->value, ['part_time', 'contract'], true),
                'assignments' => $e->componentAssignments->map(fn ($a) => [
                    'id' => $a->id,
                    'component' => $a->component?->name,
                    'kind' => $a->component?->kind->value,
                    'is_earning' => $a->component?->kind === PayrollComponentKind::Allowance,
                    'amount' => (float) $a->amount,
                    'schedule_type' => $a->schedule_type->value,
                    'schedule_label' => $a->schedule_type->label(),
                    'start_period' => $a->startPeriod?->name,
                    'end_period' => $a->endPeriod?->name,
                    'note' => $a->note,
                ])->values(),
            ]),
            'periods' => PayrollPeriod::monthly()->forActiveYear()
                ->orderByDesc('start_date')->get(['id', 'name']),
            'scheduleTypes' => collect(ScheduleType::cases())
                ->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
        ];
    }

    /** Save the institution-wide policy constants (only known keys are accepted). */
    public function updatePolicy(Request $request)
    {
        $fields = self::policyFields();

        $rules = [];
        foreach ($fields as $key => $field) {
            $rules[$key] = 'required|'.$field['rule'];
        }

        $data = $request->validate($rules);

        foreach ($data as $key => $value) {
            $type = $fields[$key]['type'];
            Setting::set(
                $key,
                $type === 'boolean' ? ($request->boolean($key) ? 'true' : 'false') : (string) $value,
                'payroll',
                $type === 'choice' ? 'string' : $type,
            );
        }

        return back()->with('success', 'Payroll policy updated. Recompute any open period to apply it.');
    }

    /** Update one employee's payroll profile flags. */
    public function updateEmployee(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'grade' => ['nullable', 'string', 'max:50'],
            'has_provident_fund' => ['required', 'boolean'],
            'statutory_exempt' => ['required', 'boolean'],
            'attendance_exempt' => ['required', 'boolean'],
            'attendance_exempt_reason' => ['nullable', 'string', 'max:255'],
        ]);

        // The reason only makes sense while the exemption is on.
        if (! $data['attendance_exempt']) {
            $data['attendance_exempt_reason'] = null;
        }

        $employee->update($data);

        return back()->with('success', "Payroll profile updated for {$employee->full_name_en}.");
    }

    /** Attach a standing allowance/deduction to an employee. */
    public function storeAssignment(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'payroll_component_id' => ['required', 'exists:payroll_components,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'schedule_type' => ['required', Rule::in(array_column(ScheduleType::cases(), 'value'))],
            'start_period_id' => ['nullable', 'exists:payroll_periods,id'],
            'end_period_id' => ['nullable', 'exists:payroll_periods,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $component = PayrollComponent::findOrFail($data['payroll_component_id']);
        if ($component->kind === PayrollComponentKind::Statutory) {
            return back()->with('error', 'Statutory components apply globally by scheme and are not assigned per employee.');
        }

        if ($data['schedule_type'] === ScheduleType::OneTime->value) {
            if (empty($data['start_period_id'])) {
                return back()->with('error', 'A one-time assignment needs the period it applies to.');
            }
            $data['end_period_id'] = $data['start_period_id'];
        }

        $data['employee_id'] = $employee->id;
        PayrollComponentAssignment::create($data);

        return back()->with('success', "\"{$component->name}\" assigned. Recompute the period to apply it.");
    }

    public function destroyAssignment(Employee $employee, PayrollComponentAssignment $assignment)
    {
        abort_if($assignment->employee_id !== $employee->id, 404);

        $assignment->delete();

        return back()->with('success', 'Assignment removed. Recompute the period to apply the change.');
    }

    /** @return array<string, array<string, mixed>> policy fields flattened, keyed by setting key */
    private static function policyFields(): array
    {
        $flat = [];
        foreach (self::POLICY as $fields) {
            foreach ($fields as $field) {
                $flat[$field['key']] = $field;
            }
        }

        return $flat;
    }

    /** Policy groups with each field's current value merged in, ready to render. */
    private static function policyValues(): array
    {
        $groups = [];
        foreach (self::POLICY as $group => $fields) {
            $groups[$group] = array_map(fn ($field) => $field + [
                'value' => Setting::get($field['key'], self::POLICY_FALLBACKS[$field['key']] ?? null),
            ], $fields);
        }

        return $groups;
    }

    /** Values used when a setting row is missing (fresh DB, seeder not yet run). */
    private const POLICY_FALLBACKS = [
        'absence_deduction_enabled' => true,
        'absence_deduction_basis' => 'base',
        'absence_deduction_rate' => 1.0,
        'absence_grace_days' => 0,
        'working_days_per_month' => 26,
        'pension_pre_tax' => true,
        'transport_allowance_limit' => 2200,
        'food_allowance_limit' => 0,
        'ot_normal_multiplier' => 1.5,
        'ot_night_multiplier' => 1.5,
        'ot_rest_multiplier' => 2.0,
        'ot_holiday_multiplier' => 2.5,
    ];

    public function store(Request $request)
    {
        $data = $this->validated($request);
        PayrollComponent::create($data);

        return back()->with('success', "Payroll component \"{$data['name']}\" created.");
    }

    public function update(Request $request, PayrollComponent $component)
    {
        $data = $this->validated($request, $component);

        // System components keep their kind; only rate/name/flags are editable.
        if ($component->is_system) {
            unset($data['kind']);
        }

        $component->update($data);

        return back()->with('success', "Payroll component \"{$component->name}\" updated.");
    }

    public function destroy(PayrollComponent $component)
    {
        if ($component->is_system) {
            return back()->with('error', 'Core components cannot be deleted — deactivate it instead.');
        }

        $component->delete();

        return back()->with('success', 'Payroll component removed.');
    }

    private function validated(Request $request, ?PayrollComponent $component = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::in(array_column(PayrollComponentKind::cases(), 'value'))],
            'calc_type' => ['required', Rule::in(array_column(PayrollComponentCalc::cases(), 'value'))],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'side' => ['nullable', Rule::in(array_column(ComponentSide::cases(), 'value'))],
            'applies_to' => ['required', Rule::in(['all', 'pension_members', 'pf_members'])],
            'taxable' => ['boolean'],
            'exempt_capped' => ['boolean'],
            'sheet_column' => ['nullable', 'string', 'max:64'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['taxable'] = $request->boolean('taxable');
        $data['exempt_capped'] = $request->boolean('exempt_capped');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? ($component?->sort_order ?? 0);

        return $data;
    }

    /** @param array<int, \BackedEnum> $cases */
    private static function options(array $cases): array
    {
        return array_map(fn ($c) => ['value' => $c->value, 'label' => method_exists($c, 'label') ? $c->label() : $c->value], $cases);
    }
}
