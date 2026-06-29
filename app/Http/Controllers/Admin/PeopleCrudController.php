<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Position;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobDescription;
use App\Models\JobDescriptionVersion;
use App\Models\Kpi;
use App\Models\User;
use App\Models\Fortnight;
use App\Models\Task;
use App\Models\Deliverable;
use App\Models\Evaluation;
use App\Models\AttendanceRecord;
use App\Models\Payslip;
use App\Models\NarrativeReport;
use App\Models\Target;
use App\Models\EvaluationPeriod;
use App\Models\Document;
use App\Enums\EmploymentType;
use App\Enums\MeasureType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;

class PeopleCrudController extends Controller
{
    // ==========================================
    // CAMPUS CRUD
    // ==========================================

    public function storeCampus(Request $request)
    {
        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        Campus::create($data);

        return redirect()->back()->with('success', "Campus created successfully.");
    }

    public function updateCampus(Request $request, Campus $campus)
    {
        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $campus->update($data);

        return redirect()->back()->with('success', "Campus updated successfully.");
    }

    public function destroyCampus(Campus $campus)
    {
        $campus->delete();

        return redirect()->back()->with('success', "Campus deleted successfully.");
    }

    // ==========================================
    // POSITION CRUD
    // ==========================================

    public function storePosition(Request $request)
    {
        $data = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', 'unique:positions,code'],
        ]);

        Position::create($data);

        return redirect()->back()->with('success', "Position created successfully.");
    }

    public function updatePosition(Request $request, Position $position)
    {
        $data = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('positions', 'code')->ignore($position->id)],
        ]);

        $position->update($data);

        return redirect()->back()->with('success', "Position updated successfully.");
    }

    public function destroyPosition(Position $position)
    {
        $position->delete();

        return redirect()->back()->with('success', "Position deleted successfully.");
    }

    // ==========================================
    // DEPARTMENT CRUD
    // ==========================================

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:departments,id'],
            'campus_id' => ['nullable', 'exists:campuses,id'],
            'head_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['boolean'],
        ]);

        Department::create($data);

        return redirect()->back()->with('success', "Department created successfully.");
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:departments,id', 'different:id'],
            'campus_id' => ['nullable', 'exists:campuses,id'],
            'head_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['boolean'],
        ]);

        $department->update($data);

        return redirect()->back()->with('success', "Department updated successfully.");
    }

    public function destroyDepartment(Department $department)
    {
        $department->delete();

        return redirect()->back()->with('success', "Department deleted successfully.");
    }

    public function showDepartment(Department $department)
    {
        $department->load([
            'parent',
            'campus',
            'head',
            'children.head',
            'employees.user',
            'employees.position',
            'targets.kpis',
            'documents.uploadedBy',
        ]);

        return Inertia::render('Admin/People/ShowDepartment', [
            'department' => $department,
        ]);
    }

    // ==========================================
    // CAMPUS SHOW
    // ==========================================

    public function showCampus(Campus $campus)
    {
        $campus->load([
            'departments.head',
        ]);

        return Inertia::render('Admin/People/ShowCampus', [
            'campus' => $campus,
        ]);
    }

    // ==========================================
    // EMPLOYEE SHOW
    // ==========================================

    public function showEmployee(Employee $employee)
    {
        $employee->load([
            'user',
            'position',
            'department.campus',
            'reportingTo.position',
            'subordinates.position',
            'tasks.target',
            'kpis.kpiable.jobDescription',
            'evaluations.period',
            'evaluations.gradeBand',
            'attendanceRecords.payrollPeriod',
            'payslips.payrollPeriod',
            'payslips.lines',
            'narrativeReports.period',
            'documents.uploadedBy',
            'statusChanges.changedBy',
        ]);

        // Job descriptions linked through position
        $jobDescriptions = $employee->position
            ? \App\Models\JobDescription::where('position_id', $employee->position_id)
                ->with(['currentVersion', 'versions'])
                ->get()
            : collect();

        // Deliverables owned by the employee's user account
        $deliverables = $employee->user_id
            ? Deliverable::where('user_id', $employee->user_id)
                ->with('fortnight')
                ->latest()
                ->get()
            : collect();

        return Inertia::render('Admin/People/ShowEmployee', [
            'employee'          => $employee,
            'jobDescriptions'   => $jobDescriptions,
            'deliverables'      => $deliverables,
            'fortnights'        => Fortnight::orderBy('start_date', 'desc')->take(12)->get(),
            'targets'           => Target::orderBy('name')->get(),
            'evaluationPeriods' => EvaluationPeriod::orderBy('start_date', 'desc')->get(),
            'credentials'       => $employee->user ? [
                'password_changed' => $employee->user->password_changed,
                'default_password' => $employee->user->password_changed ? null : $employee->user->default_password,
                'reset_link_requested_at' => $employee->user->password_reset_requested_at,
            ] : null,
        ]);
    }

    /**
     * Email the employee a token-based password recovery link (standard
     * Laravel password broker — they choose their own new password).
     */
    public function sendResetLink(Employee $employee)
    {
        if (! $employee->user) {
            return redirect()->back()->with('error', 'This employee has no linked user account.');
        }

        $status = Password::sendResetLink(['email' => $employee->user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return redirect()->back()->with('error', __($status));
        }

        $employee->user->update(['password_reset_requested_at' => now()]);

        return redirect()->back()->with('success', "Password reset link sent to {$employee->user->email}.");
    }

    // ==========================================
    // EMPLOYEE CRUD
    // ==========================================

    public function storeEmployee(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'unique:employees,user_id'],
            'staff_no' => ['required', 'string', 'max:255', 'unique:employees,staff_no'],
            'full_name_en' => ['required', 'string', 'max:255'],
            'full_name_am' => ['nullable', 'string', 'max:255'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'reporting_to_id' => ['nullable', 'exists:employees,id'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'legal_daily_hour_limit' => ['required', 'integer', 'min:1', 'max:24'],
            'hired_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        Employee::create($data);

        return redirect()->back()->with('success', "Employee created successfully.");
    }

    public function updateEmployee(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($employee->id)],
            'staff_no' => ['required', 'string', 'max:255', Rule::unique('employees', 'staff_no')->ignore($employee->id)],
            'full_name_en' => ['required', 'string', 'max:255'],
            'full_name_am' => ['nullable', 'string', 'max:255'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'reporting_to_id' => ['nullable', 'exists:employees,id', 'different:id'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'legal_daily_hour_limit' => ['required', 'integer', 'min:1', 'max:24'],
            'hired_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $employee->update($data);

        return redirect()->back()->with('success', "Employee updated successfully.");
    }

    public function destroyEmployee(Employee $employee)
    {
        $employee->delete();

        return redirect()->back()->with('success', "Employee deleted successfully.");
    }

    // ==========================================
    // JOB DESCRIPTION & VERSIONS CRUD
    // ==========================================

    /** Allowed JD item categories — a small, standardized vocabulary. */
    private const ITEM_CATEGORIES = ['responsibility', 'authority', 'qualification', 'relationship'];

    /** Validation rules for the structured, repeatable JD items. */
    private function itemRules(): array
    {
        return [
            'body' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category' => ['required', Rule::in(self::ITEM_CATEGORIES)],
            'items.*.title_en' => ['required', 'string', 'max:255'],
            'items.*.title_am' => ['nullable', 'string', 'max:255'],
            'items.*.is_kpi' => ['boolean'],
            'items.*.measure_type' => ['nullable', Rule::in(array_column(MeasureType::cases(), 'value'))],
            'items.*.target_value' => ['nullable', 'numeric'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /** Create KPIs (owned by the JD version) for every item flagged is_kpi. */
    private function promoteItemKpis(JobDescriptionVersion $version): int
    {
        $count = 0;

        foreach ($version->items ?? [] as $item) {
            if (empty($item['is_kpi'])) {
                continue;
            }

            $version->kpis()->create([
                'title_en' => $item['title_en'],
                'title_am' => $item['title_am'] ?? null,
                'measure_type' => $item['measure_type'] ?? MeasureType::Qualitative->value,
                'target_value' => $item['target_value'] ?? null,
                'unit' => $item['unit'] ?? null,
                'weight' => $item['weight'] ?? 1,
                'status' => 'created',
            ]);
            $count++;
        }

        return $count;
    }

    public function storeJobDescription(Request $request)
    {
        $data = $request->validate([
            'position_id' => ['required', 'exists:positions,id', 'unique:job_descriptions,position_id'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
            'effective_from' => ['nullable', 'date'],
        ] + $this->itemRules());

        $kpiCount = 0;

        DB::transaction(function () use ($data, &$kpiCount) {
            $jd = JobDescription::create([
                'position_id' => $data['position_id'],
                'title_en' => $data['title_en'],
                'title_am' => $data['title_am'] ?? null,
            ]);

            $version = JobDescriptionVersion::create([
                'job_description_id' => $jd->id,
                'version_no' => 1,
                'body' => $data['body'] ?? null,
                'items' => $data['items'],
                'effective_from' => $data['effective_from'] ?? null,
            ]);

            $jd->update(['current_version_id' => $version->id]);
            $kpiCount = $this->promoteItemKpis($version);
        });

        $suffix = $kpiCount ? " {$kpiCount} item(s) promoted to KPIs." : '';

        return redirect()->back()->with('success', "Job Description created with Version 1.{$suffix}");
    }

    public function updateJobDescription(Request $request, JobDescription $jobDescription)
    {
        $data = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
        ]);

        $jobDescription->update($data);

        return redirect()->back()->with('success', "Job Description meta updated successfully.");
    }

    public function storeJobDescriptionVersion(Request $request, JobDescription $jobDescription)
    {
        $data = $request->validate([
            'effective_from' => ['nullable', 'date'],
        ] + $this->itemRules());

        $nextVersionNo = ($jobDescription->versions()->max('version_no') ?? 0) + 1;
        $kpiCount = 0;

        DB::transaction(function () use ($jobDescription, $data, $nextVersionNo, &$kpiCount) {
            $version = JobDescriptionVersion::create([
                'job_description_id' => $jobDescription->id,
                'version_no' => $nextVersionNo,
                'body' => $data['body'] ?? null,
                'items' => $data['items'],
                'effective_from' => $data['effective_from'] ?? null,
            ]);

            $kpiCount = $this->promoteItemKpis($version);
        });

        $suffix = $kpiCount ? " {$kpiCount} item(s) promoted to KPIs." : '';

        return redirect()->back()->with('success', "Job Description Version {$nextVersionNo} created.{$suffix}");
    }

    public function activateJobDescriptionVersion(Request $request, JobDescription $jobDescription, JobDescriptionVersion $version)
    {
        abort_if($version->job_description_id !== $jobDescription->id, 404);

        $jobDescription->update(['current_version_id' => $version->id]);

        return redirect()->back()->with('success', "Job Description version {$version->version_no} is now active.");
    }

    public function destroyJobDescription(JobDescription $jobDescription)
    {
        $jobDescription->delete();

        return redirect()->back()->with('success', "Job Description deleted successfully.");
    }
}
