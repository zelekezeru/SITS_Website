<?php

namespace App\Http\Controllers\Bookstore;

use App\Http\Controllers\Controller;
use App\Models\Shelf;
use App\Models\StoreRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Shelves within a store room. Each gets its own QR label. */
class ShelfController extends Controller
{
    public function store(Request $request, StoreRoom $store): RedirectResponse
    {
        $validated = $request->validate([
            'code'       => 'required|string|max:30|unique:shelves,code,NULL,id,store_room_id,'.$store->id,
            'label'      => 'nullable|string|max:255',
            'capacity'   => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $store->shelves()->create($validated);

        return back()->with('success', 'Shelf added.');
    }

    public function update(Request $request, Shelf $shelf): RedirectResponse
    {
        $validated = $request->validate([
            'code'       => 'required|string|max:30|unique:shelves,code,'.$shelf->id.',id,store_room_id,'.$shelf->store_room_id,
            'label'      => 'nullable|string|max:255',
            'capacity'   => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $shelf->update($validated);

        return back()->with('success', 'Shelf updated.');
    }

    public function destroy(Shelf $shelf): RedirectResponse
    {
        $onHand = (int) $shelf->sections()
            ->withSum('stocks as quantity_sum', 'quantity')
            ->get()
            ->sum('quantity_sum');

        if ($onHand > 0) {
            return back()->with('error', 'This shelf still holds stock. Transfer it out before archiving.');
        }

        $shelf->delete();

        return back()->with('success', 'Shelf archived.');
    }
}
