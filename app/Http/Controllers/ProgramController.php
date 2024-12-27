<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\ProgramStoreRequest;
use App\Http\Requests\ProgramUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {

        $programs = Program::paginate(10); // Use pagination to avoid loading too many records at once

        return view('programs.index', compact('programs'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {

        $program = new Program;
        return view('programs.create', compact('program'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProgramStoreRequest $request): RedirectResponse
    {

        Program::create($request->validated());

        // Redirect to the create page with a success message
        return redirect()->route('programs.create')
            ->with('success', 'Program created successfully.');
    }


    /**
     * Display the specified resource.
     */
    public function show(Program $program): View
    {
        return view('programs.show', compact('program'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Program $program): View
    {
        return view('programs.edit', compact('program'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProgramUpdateRequest $request, Program $program): RedirectResponse
    {
        $data = $request->validated();
        $program->update($data);

        return redirect()->route('programs.index')
            ->with('success', 'Program updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Program $program)
    {
        $program->delete();

        return redirect()->route('programs.index')
            ->with('success', 'Program deleted successfully');
    }
}
