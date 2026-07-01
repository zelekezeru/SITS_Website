<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampusController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Spatial/Campuses/Index', [
            'campuses' => Campus::withCount('floors')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Spatial/Campuses/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'code'      => 'required|string|max:20|unique:campuses,code',
            'address'   => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        Campus::create($validated);

        return redirect()->route('campuses.index')
            ->with('success', 'Campus created successfully.');
    }

    public function show(Campus $campus): Response
    {
        $campus->load(['floors.rows.shelfBoxes']);

        return Inertia::render('Spatial/Tree', [
            'campus' => $campus,
            'floors' => $campus->floors()->with('rows')->orderBy('level')->get(),
        ]);
    }

    public function edit(Campus $campus): Response
    {
        return Inertia::render('Spatial/Campuses/Edit', [
            'campus' => $campus,
        ]);
    }

    public function update(Request $request, Campus $campus): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'code'      => 'required|string|max:20|unique:campuses,code,'.$campus->id,
            'address'   => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $campus->update($validated);

        return redirect()->route('campuses.index')
            ->with('success', 'Campus updated successfully.');
    }

    public function destroy(Campus $campus): RedirectResponse
    {
        $campus->delete(); // soft delete — floors/rows/shelf_boxes cascade

        return redirect()->route('campuses.index')
            ->with('success', 'Campus archived.');
    }
}
