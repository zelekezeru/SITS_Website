<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ComponentSide;
use App\Enums\PayrollComponentCalc;
use App\Enums\PayrollComponentKind;
use App\Http\Controllers\Controller;
use App\Models\PayrollComponent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin-only configuration of the payroll component registry: allowance and
 * deduction titles and the statutory pension / provident-fund rates. Seeded
 * `is_system` rows can be renamed/re-rated/deactivated but not deleted, and
 * their kind is fixed.
 */
class PayrollConfigController extends Controller
{
    private const SHEET_COLUMNS = [
        'allowance' => ['transport_allowance', 'housing_allowance', 'mobile_allowance', 'cash_allowance'],
        'deduction' => ['salary_advance', 'kircha_deduction', 'other_deduction'],
        'statutory' => ['employee_pension', 'employer_pension', 'provident_fund_employee', 'provident_fund_employer'],
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
