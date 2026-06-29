<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Year;
use App\Models\Quarter;
use App\Models\Fortnight;
use App\Models\Day;
use App\Models\Employee;
use App\Models\Deliverable;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CalendarCrudController extends Controller
{
    /**
     * Generate Quarters, Fortnights, and Days for the active Year.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'year_id' => ['required', 'exists:years,id'],
        ]);

        $year = Year::findOrFail($request->year_id);

        if (!$year->start_date || !$year->end_date) {
            return redirect()->back()->with('error', "The Fiscal Year must have a start and end date before generating calendar records.");
        }

        DB::transaction(function () use ($year) {
            // Clear existing quarters (cascades to fortnights and sets days' fortnight_id to null)
            foreach ($year->quarters as $q) {
                foreach ($q->fortnights as $f) {
                    Day::where('fortnight_id', $f->id)->update(['fortnight_id' => null]);
                    $f->delete();
                }
                $q->delete();
            }

            $start = Carbon::parse($year->start_date);
            $end = Carbon::parse($year->end_date);

            // Generate 4 Quarters
            for ($i = 1; $i <= 4; $i++) {
                $qStart = $start->copy()->addMonths(($i - 1) * 3);
                $qEnd = $start->copy()->addMonths($i * 3)->subDay();

                // Cap the last quarter at the year's end date
                if ($i === 4 || $qEnd->gt($end)) {
                    $qEnd = $end->copy();
                }

                $quarter = Quarter::create([
                    'year_id' => $year->id,
                    'name' => "Q{$i}",
                    'start_date' => $qStart->toDateString(),
                    'end_date' => $qEnd->toDateString(),
                ]);

                // Generate Fortnights for this Quarter
                $fStart = $qStart->copy();
                $fIndex = 1;

                while ($fStart->lt($qEnd)) {
                    $fEnd = $fStart->copy()->addDays(13);
                    if ($fEnd->gt($qEnd)) {
                        $fEnd = $qEnd->copy();
                    }

                    $fortnightName = "Q{$i}-F{$fIndex}";
                    $fortnight = Fortnight::create([
                        'quarter_id' => $quarter->id,
                        'name' => $fortnightName,
                        'start_date' => $fStart->toDateString(),
                        'end_date' => $fEnd->toDateString(),
                    ]);

                    // Generate Days for this Fortnight
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
        });

        return redirect()->back()->with('success', "Quarters, Fortnights, and Days generated successfully for {$year->label}.");
    }

    // ==========================================
    // FORTNIGHT SHOW
    // ==========================================

    public function showFortnight(Request $request, Fortnight $fortnight)
    {
        $fortnight->load('quarter');

        $employeeId = $request->query('employee_id');

        // Load days with their tasks (optionally filtered)
        $days = $fortnight->days()->with([
            'tasks' => function ($q) use ($employeeId) {
                if ($employeeId) {
                    $q->where('employee_id', $employeeId);
                }
            },
        ])->orderBy('date')->get();

        // Tasks in this fortnight (via pivot), optionally filtered
        $tasksQuery = $fortnight->tasks()->with(['employee.position', 'target']);
        if ($employeeId) {
            $tasksQuery->where('employee_id', $employeeId);
        }
        $tasks = $tasksQuery->get();

        // Deliverables: find deliverables whose deadline falls within the fortnight
        $deliverablesQuery = Deliverable::whereBetween('deadline', [
            $fortnight->start_date, $fortnight->end_date,
        ])->with(['user', 'reviewedByUser']);
        if ($employeeId) {
            $employee = Employee::find($employeeId);
            if ($employee) {
                $deliverablesQuery->where('user_id', $employee->user_id);
            }
        }
        $deliverables = $deliverablesQuery->get();

        $selectedEmployee = $employeeId ? Employee::with('position')->find($employeeId) : null;

        return Inertia::render('Admin/Calendar/FortnightDetail', [
            'fortnight'        => $fortnight,
            'days'             => $days,
            'tasks'            => $tasks,
            'deliverables'     => $deliverables,
            'employees'        => Employee::with('position')->where('is_active', true)->orderBy('full_name_en')->get(),
            'selectedEmployee' => $selectedEmployee,
        ]);
    }

    // ==========================================
    // QUARTER CRUD
    // ==========================================

    public function storeQuarter(Request $request)
    {
        $data = $request->validate([
            'year_id' => ['required', 'exists:years,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        Quarter::create($data);

        return redirect()->back()->with('success', "Quarter created successfully.");
    }

    public function updateQuarter(Request $request, Quarter $quarter)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $quarter->update($data);

        return redirect()->back()->with('success', "Quarter updated successfully.");
    }

    public function destroyQuarter(Quarter $quarter)
    {
        $quarter->delete();

        return redirect()->back()->with('success', "Quarter deleted successfully.");
    }

    // ==========================================
    // FORTNIGHT CRUD
    // ==========================================

    public function storeFortnight(Request $request)
    {
        $data = $request->validate([
            'quarter_id' => ['required', 'exists:quarters,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        Fortnight::create($data);

        return redirect()->back()->with('success', "Fortnight created successfully.");
    }

    public function updateFortnight(Request $request, Fortnight $fortnight)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $fortnight->update($data);

        return redirect()->back()->with('success', "Fortnight updated successfully.");
    }

    public function destroyFortnight(Fortnight $fortnight)
    {
        $fortnight->delete();

        return redirect()->back()->with('success', "Fortnight deleted successfully.");
    }

    // ==========================================
    // DAY CRUD
    // ==========================================

    public function updateDay(Request $request, Day $day)
    {
        $data = $request->validate([
            'fortnight_id' => ['nullable', 'exists:fortnights,id'],
        ]);

        $day->update($data);

        return redirect()->back()->with('success', "Day assignment updated successfully.");
    }
}
