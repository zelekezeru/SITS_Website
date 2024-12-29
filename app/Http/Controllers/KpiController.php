<?php

namespace App\Http\Controllers;

use App\Models\Kpi;
use App\Models\Task;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Task $task)
    {
        // Validate request data
        $request->validate([
            'performance_indicators' => 'required|string',
            'qualitative' => 'required|string',
            'quantitative' => 'required|integer',
            'ratings' => 'required|numeric|min:0|max:5',
        ]);
    
        // Manually add the task_id to the request data
        $validatedData = $request->only('performance_indicators', 'qualitative', 'quantitative', 'ratings');
        $validatedData['task_id'] = $task->id; // Explicitly set task_id
        // Create KPI associated with the task
        $task->kpis()->create($validatedData);
    
        // Redirect with success message
        return redirect()->route('tasks.show', $task->id)
                         ->with('success', 'KPI added successfully.');
    }
    
    /**
     * Display the specified resource.
     */
    public function show(Kpi $kpi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Kpi $kpi)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KPI $kpi)
    {
        $request->validate([
            'performance_indicators' => 'required|string',
            'qualitative' => 'required|string', 
            'quantitative' => 'required|integer',
            'ratings' => 'required|numeric|min:0|max:5',
        ]);
    
        $kpi->update($request->only('performance_indicators', 'qualitative', 'quantitative', 'ratings'));
    
        return redirect()->route('tasks.show', $kpi->task_id)->with('success', 'KPI updated successfully.');
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Kpi $kpi)
    {
        // $task_id = $kpi->task_id;
        $kpi->delete();

        return redirect()->route('tasks.show', $kpi->task_id)
                        ->with('success', 'KPI Deleted Successfully');
    }
}
