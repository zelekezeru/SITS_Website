<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\Floor;
use App\Models\Row;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RowController extends Controller
{
    public function store(Request $request, Floor $floor): RedirectResponse
    {
        $validated = $request->validate([
            'label'        => 'required|string|max:50',
            'subject_area' => 'nullable|string|max:100',
        ]);

        $floor->rows()->create($validated);

        return redirect()->route('campuses.show', $floor->campus_id)
            ->with('success', 'Row added.');
    }

    public function update(Request $request, Row $row): RedirectResponse
    {
        $validated = $request->validate([
            'label'        => 'required|string|max:50',
            'subject_area' => 'nullable|string|max:100',
        ]);

        $row->update($validated);

        return redirect()->route('campuses.show', $row->floor->campus_id)
            ->with('success', 'Row updated.');
    }

    public function destroy(Row $row): RedirectResponse
    {
        $campusId = $row->floor->campus_id;
        $row->delete();

        return redirect()->route('campuses.show', $campusId)
            ->with('success', 'Row archived.');
    }
}
