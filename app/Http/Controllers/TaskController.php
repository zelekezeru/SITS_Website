<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {

    $tasks = Task::paginate(10); // Use pagination to avoid loading too many records at once

    return view('tasks.index', compact('tasks'));

    }

    /**
     * Display a listing of the resource.
     */
    public function list(): View
    {

    $tasks = Task::paginate(10); // Use pagination to avoid loading too many records at once

    return view('tasks.list', compact('tasks'));

    }



    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $task = new Task;
        return view('tasks.create', compact('task'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TaskStoreRequest $request) : RedirectResponse
    {
        Task::create($request->validated());

        return redirect()->route('tasks.list')
            ->with('success', 'Task created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task) : View
    {
        return view('tasks.show', compact('task'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task) : View
    {
        return view('tasks.edit', compact('task'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaskUpdateRequest $request, Task $task) : RedirectResponse
    {
        $data = $request->validated();
        
        $task->update($data);

        return redirect()->route('tasks.list')
            ->with('success', 'Task updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->route('tasks.list')
            ->with('success', 'Task deleted successfully');
    }
}
