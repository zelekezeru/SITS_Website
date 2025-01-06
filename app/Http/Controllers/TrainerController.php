<?php

namespace App\Http\Controllers;

use App\Models\Trainer;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\TrainerStoreRequest;
use App\Http\Requests\TrainerUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {

    $trainers = Trainer::paginate(10); // Use pagination to avoid loading too many records at once

    return view('trainers.index', compact('trainers'));

    }

    /**
     * Display a listing of the resource.
     */
    public function list(): View
    {

    $trainers = Trainer::paginate(10); // Use pagination to avoid loading too many records at once

    return view('trainers.list', compact('trainers'));

    }



    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $trainer = new Trainer;
        return view('trainers.create', compact('trainer'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TrainerStoreRequest $request) : RedirectResponse
    {
        $data = $request->validated();
    
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('trainerImages', 'public');
            $data['image'] = $imagePath;
        }
    
        Trainer::create($data);
    
        return redirect()->route('trainers.list')
            ->with('success', 'Trainer created successfully.');
    }
    
    /**
     * Display the specified resource.
     */
    public function show(Trainer $trainer) : View
    {
        return view('trainers.show', compact('trainer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Trainer $trainer) : View
    {
        return view('trainers.edit', compact('trainer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TrainerUpdateRequest $request, Trainer $trainer): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
            $data['image'] = $imagePath;
        }

        $trainer->update($data);

        return redirect()->route('trainers.list')
            ->with('success', 'Trainer updated successfully');
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trainer $trainer)
    {
        $trainer->delete();

        return redirect()->route('trainers.list')
            ->with('success', 'Trainer deleted successfully');
    }
}
