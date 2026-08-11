<?php

namespace App\Http\Controllers\Bookstore;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Shelf;
use App\Models\StoreRoom;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Store rooms — the top of the QR-labelled warehouse tree. */
class StoreRoomController extends Controller
{
    public function index(): Response
    {
        $stores = StoreRoom::with(['campus:id,name,name_en', 'manager:id,name'])
            ->withCount('shelves')
            ->orderBy('name')
            ->get()
            ->map(fn (StoreRoom $store) => array_merge($store->toArray(), [
                'total_on_hand' => $store->total_on_hand,
            ]));

        return Inertia::render('Bookstore/Stores/Index', [
            'stores'  => $stores,
            'options' => $this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        StoreRoom::create($this->validated($request));

        return back()->with('success', 'Store room created.');
    }

    /** The whole tree for one store, with per-section stock — the picking map. */
    public function show(StoreRoom $store): Response
    {
        $store->load([
            'campus:id,name,name_en',
            'manager:id,name',
            'shelves' => fn ($q) => $q->ordered()->with([
                'sections' => fn ($s) => $s->ordered()->with('stocks.bookTitle:id,code,title'),
            ]),
        ]);

        return Inertia::render('Bookstore/Stores/Show', [
            'store'   => $store,
            'options' => $this->formOptions(),
            'totals'  => [
                'sections' => $store->sections()->count(),
                'on_hand'  => $store->total_on_hand,
            ],
        ]);
    }

    public function update(Request $request, StoreRoom $store): RedirectResponse
    {
        $store->update($this->validated($request, $store));

        return back()->with('success', 'Store room updated.');
    }

    public function destroy(StoreRoom $store): RedirectResponse
    {
        if ($store->total_on_hand > 0) {
            return back()->with('error', 'This store still holds stock. Transfer it out before archiving.');
        }

        $store->delete();

        return redirect()->route('bookstore.stores.index')->with('success', 'Store room archived.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?StoreRoom $store = null): array
    {
        return $request->validate([
            'campus_id'     => 'nullable|exists:campuses,id',
            'name'          => 'required|string|max:255',
            'code'          => 'required|string|max:30|unique:store_rooms,code'.($store ? ",{$store->id}" : ''),
            'location_note' => 'nullable|string|max:255',
            'manager_id'    => 'nullable|exists:users,id',
            'is_active'     => 'boolean',
            'notes'         => 'nullable|string',
        ]);
    }

    /** @return array<string, mixed> */
    protected function formOptions(): array
    {
        return [
            'campuses' => Campus::orderBy('name')->get(['id', 'name', 'name_en']),
            'managers' => User::orderBy('name')->limit(200)->get(['id', 'name']),
        ];
    }
}
