<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\Campus;
use App\Models\Floor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FloorController extends Controller
{
    public function store(Request $request, Campus $campus): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'level' => 'required|integer|min:0',
        ]);

        $campus->floors()->create($validated);

        return redirect()->route('campuses.show', $campus)
            ->with('success', 'Floor added.');
    }

    public function update(Request $request, Floor $floor): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'level' => 'required|integer|min:0',
        ]);

        $floor->update($validated);

        return redirect()->route('campuses.show', $floor->campus_id)
            ->with('success', 'Floor updated.');
    }

    public function destroy(Floor $floor): RedirectResponse
    {
        $campusId = $floor->campus_id;
        $floor->delete();

        return redirect()->route('campuses.show', $campusId)
            ->with('success', 'Floor archived.');
    }
}
