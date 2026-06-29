<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Year;
use App\Models\Strategy;
use App\Models\Goal;
use App\Models\Target;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StrategyCrudController extends Controller
{
    // ==========================================
    // FISCAL YEARS CRUD
    // ==========================================

    public function storeYear(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255', 'unique:years,label'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $year = Year::create($data);

        // If it's the first year, activate it
        if (Year::count() === 1) {
            $year->update(['active' => true]);
        }

        return redirect()->back()->with('success', "Fiscal Year {$year->label} created successfully.");
    }

    public function updateYear(Request $request, Year $year)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255', Rule::unique('years', 'label')->ignore($year->id)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $year->update($data);

        return redirect()->back()->with('success', "Fiscal Year {$year->label} updated successfully.");
    }

    public function destroyYear(Year $year)
    {
        if ($year->active) {
            return redirect()->back()->with('error', "Cannot delete the active Fiscal Year. Please activate another year first.");
        }

        $year->delete();

        return redirect()->back()->with('success', "Fiscal Year deleted successfully.");
    }

    public function activateYear(Year $year)
    {
        Year::query()->update(['active' => false]);
        $year->update(['active' => true]);

        return redirect()->back()->with('success', "Fiscal Year {$year->label} is now active.");
    }

    public function showYear(Year $year)
    {
        $year->load([
            'quarters.fortnights.days',
        ]);

        return Inertia::render('Admin/Strategy/ShowYear', [
            'year' => $year,
        ]);
    }

    // ==========================================
    // STRATEGIES CRUD
    // ==========================================

    public function storeStrategy(Request $request)
    {
        $data = $request->validate([
            'year_id' => ['required', 'exists:years,id'],
            'pillar' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Strategy::create($data);

        return redirect()->back()->with('success', "Strategy created successfully.");
    }

    public function updateStrategy(Request $request, Strategy $strategy)
    {
        $data = $request->validate([
            'year_id' => ['required', 'exists:years,id'],
            'pillar' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $strategy->update($data);

        return redirect()->back()->with('success', "Strategy updated successfully.");
    }

    public function destroyStrategy(Strategy $strategy)
    {
        $strategy->delete();

        return redirect()->back()->with('success', "Strategy deleted successfully.");
    }

    // ==========================================
    // GOALS CRUD
    // ==========================================

    public function storeGoal(Request $request)
    {
        $data = $request->validate([
            'strategy_id' => ['required', 'exists:strategies,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Goal::create($data);

        return redirect()->back()->with('success', "Goal created successfully.");
    }

    public function updateGoal(Request $request, Goal $goal)
    {
        $data = $request->validate([
            'strategy_id' => ['required', 'exists:strategies,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $goal->update($data);

        return redirect()->back()->with('success', "Goal updated successfully.");
    }

    public function destroyGoal(Goal $goal)
    {
        $goal->delete();

        return redirect()->back()->with('success', "Goal deleted successfully.");
    }

    // ==========================================
    // TARGETS CRUD
    // ==========================================

    public function storeTarget(Request $request)
    {
        $data = $request->validate([
            'goal_id' => ['required', 'exists:goals,id'],
            'year_id' => ['required', 'exists:years,id'],
            'name' => ['required', 'string', 'max:255'],
            'budget' => ['required', 'numeric', 'min:0'],
            'value' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:255'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['exists:departments,id'],
        ]);

        $target = Target::create($data);
        
        if ($request->has('department_ids')) {
            $target->departments()->sync($request->department_ids);
        }

        return redirect()->back()->with('success', "Target created successfully.");
    }

    public function updateTarget(Request $request, Target $target)
    {
        $data = $request->validate([
            'goal_id' => ['required', 'exists:goals,id'],
            'year_id' => ['required', 'exists:years,id'],
            'name' => ['required', 'string', 'max:255'],
            'budget' => ['required', 'numeric', 'min:0'],
            'value' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:255'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['exists:departments,id'],
        ]);

        $target->update($data);
        
        if ($request->has('department_ids')) {
            $target->departments()->sync($request->department_ids);
        } else {
            $target->departments()->detach();
        }

        return redirect()->back()->with('success', "Target updated successfully.");
    }

    public function destroyTarget(Target $target)
    {
        $target->departments()->detach();
        $target->delete();

        return redirect()->back()->with('success', "Target deleted successfully.");
    }
}
