<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Task;
use Illuminate\Http\Request;

class FeedbackController extends Controller
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
            'comment' => 'required|string',
        ]);
    
        // Manually add the task_id to the request data
        $validatedData = $request->only('comment');
        $validatedData['task_id'] = $task->id; // Explicitly set task_id
        // Create FEEDBACK associated with the task
        $task->feedbacks()->create($validatedData);
    
        // Redirect with success message
        return redirect()->route('tasks.show', $task->id)
                         ->with('success', 'Feedback added successfully.');
    }
    /**
     * Display the specified resource.
     */
    public function show(Feedback $feedback)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Feedback $feedback)
    {
        //
    }

   /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Feedback $feedback)
    {
        $request->validate([
            'comment' => 'required|string'
        ]);
    
        $feedback->update($request->only('comment'));
    
        return redirect()->route('tasks.show', $feedback->task_id)->with('success', 'FEEDBACK updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Feedback $feedback)
    {
        $feedback->delete();

        return redirect()->route('tasks.show', $feedback->task_id)
                        ->with('success', 'Feedback Deleted Successfully');
    }
}
