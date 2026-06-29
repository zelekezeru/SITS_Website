<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Year;
use App\Support\FiscalYear;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    /**
     * Choose which fiscal year to browse. Passing no year (or null) returns to
     * the institution's active year. This only changes what *this* user sees;
     * it does not change which year is active.
     */
    public function view(Request $request)
    {
        $data = $request->validate([
            'year_id' => ['nullable', 'exists:years,id'],
        ]);

        FiscalYear::setViewing($data['year_id'] ?? null);

        $year = isset($data['year_id']) ? Year::find($data['year_id']) : FiscalYear::active();
        $label = $year?->label ?? 'the active year';

        return redirect()->back()->with('success', "Now viewing {$label}.");
    }
}
