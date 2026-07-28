<?php

namespace App\Http\Controllers;

use App\Enums\ScheduleType;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollComponentAssignment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeePayrollController extends Controller
{
    public function getConfig(Employee $employee)
    {
        $employee->load(['componentAssignments.component', 'componentAssignments.startPeriod', 'componentAssignments.endPeriod']);

        return response()->json([
            'flags' => [
                'has_provident_fund' => $employee->has_provident_fund,
                'statutory_exempt' => $employee->statutory_exempt,
                'attendance_exempt' => $employee->attendance_exempt,
                'attendance_exempt_reason' => $employee->attendance_exempt_reason,
            ],
            'assignments' => $employee->componentAssignments->map(fn($a) => [
                'id' => $a->id,
                'payroll_component_id' => $a->payroll_component_id,
                'component_name' => $a->component?->name,
                'component_type' => $a->component?->kind->value,
                'amount' => (float) $a->amount,
                'schedule_type' => $a->schedule_type->value,
                'start_period_name' => $a->startPeriod?->name,
                'end_period_name' => $a->endPeriod?->name,
                'is_active' => $a->is_active,
                'note' => $a->note,
            ])
        ]);
    }

    public function updateConfig(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'has_provident_fund' => ['required', 'boolean'],
            'statutory_exempt' => ['required', 'boolean'],
            'attendance_exempt' => ['required', 'boolean'],
            'attendance_exempt_reason' => ['nullable', 'string', 'max:255'],
        ]);

        // Clearing the exemption also clears any one-month scope it carried.
        if (! $data['attendance_exempt']) {
            $data['attendance_exempt_reason'] = null;
            $data['attendance_exempt_period_id'] = null;
        }

        $employee->update($data);

        return response()->json(['message' => 'Payroll configuration updated.']);
    }

    public function storeAssignment(Request $request, Employee $employee)
    {
        // The period selects submit '' for "none"; normalise before validating so
        // the exists rules and the stored foreign keys see a real null.
        $request->merge([
            'start_period_id' => $request->input('start_period_id') ?: null,
            'end_period_id' => $request->input('end_period_id') ?: null,
        ]);

        $data = $request->validate([
            'payroll_component_id' => ['required', 'exists:payroll_components,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'schedule_type' => ['required', Rule::in(array_column(ScheduleType::cases(), 'value'))],
            'start_period_id' => ['nullable', 'exists:payroll_periods,id'],
            'end_period_id' => ['nullable', 'exists:payroll_periods,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['schedule_type'] === ScheduleType::OneTime->value) {
            if (empty($data['start_period_id'])) {
                throw ValidationException::withMessages([
                    'start_period_id' => 'Choose the period this one-time entry applies to.',
                ]);
            }
            $data['end_period_id'] = $data['start_period_id'];
        }

        $component = PayrollComponent::findOrFail($data['payroll_component_id']);
        if ($component->kind === \App\Enums\PayrollComponentKind::Statutory) {
            throw ValidationException::withMessages([
                'payroll_component_id' => 'Statutory components apply globally by scheme and cannot be assigned per employee.',
            ]);
        }

        $data['employee_id'] = $employee->id;
        PayrollComponentAssignment::create($data);

        return response()->json(['message' => "\"{$component->name}\" assigned. Recompute the period to apply it."]);
    }

    public function destroyAssignment(Employee $employee, PayrollComponentAssignment $assignment)
    {
        if ($assignment->employee_id !== $employee->id) {
            abort(403);
        }

        $assignment->delete();

        return response()->json(['message' => 'Assignment removed.']);
    }
}
