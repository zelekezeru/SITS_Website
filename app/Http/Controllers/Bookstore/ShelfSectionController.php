<?php

namespace App\Http\Controllers\Bookstore;

use App\Http\Controllers\Controller;
use App\Models\Shelf;
use App\Models\ShelfSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shelf sections — the only level that holds stock, and the QR a store keeper
 * scans most often.
 */
class ShelfSectionController extends Controller
{
    public function store(Request $request, Shelf $shelf): RedirectResponse
    {
        $validated = $request->validate([
            'code'       => 'required|string|max:30|unique:shelf_sections,code,NULL,id,shelf_id,'.$shelf->id,
            'name'       => 'nullable|string|max:255',
            'capacity'   => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $shelf->sections()->create($validated);

        return back()->with('success', 'Shelf section added. Print its QR label before racking books.');
    }

    /** What is physically in this section, plus its recent ledger lines. */
    public function show(ShelfSection $section): Response
    {
        $section->load([
            'shelf.storeRoom',
            'stocks.bookTitle:id,code,title,unit_price,reorder_level',
        ]);

        return Inertia::render('Bookstore/Sections/Show', [
            'section'   => array_merge($section->toArray(), [
                'path'               => $section->path,
                'total_on_hand'      => $section->total_on_hand,
                'remaining_capacity' => $section->remaining_capacity,
            ]),
            'movements' => $section->movements()
                ->with(['bookTitle:id,code,title', 'performedBy:id,name'])
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->paginate(25),
        ]);
    }

    public function update(Request $request, ShelfSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'code'       => 'required|string|max:30|unique:shelf_sections,code,'.$section->id.',id,shelf_id,'.$section->shelf_id,
            'name'       => 'nullable|string|max:255',
            'capacity'   => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $section->update($validated);

        return back()->with('success', 'Shelf section updated.');
    }

    public function destroy(ShelfSection $section): RedirectResponse
    {
        if ($section->total_on_hand > 0) {
            return back()->with('error', 'This section still holds stock. Transfer it out before archiving.');
        }

        $section->delete();

        return back()->with('success', 'Shelf section archived.');
    }
}
