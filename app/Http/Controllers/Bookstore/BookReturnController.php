<?php

namespace App\Http\Controllers\Bookstore;

use App\Http\Controllers\Controller;
use App\Models\BookDispatch;
use App\Models\BookReturn;
use App\Models\BookTitle;
use App\Models\Campus;
use App\Models\Center;
use App\Models\ShelfSection;
use App\Services\Bookstore\ReturnService;
use App\Services\Bookstore\WorkflowException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Unsold copies coming back from a centre, re-shelved and reconciled. */
class BookReturnController extends Controller
{
    public function __construct(private readonly ReturnService $returns)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Bookstore/Returns/Index', [
            'returns' => BookReturn::with([
                'center:id,name',
                'campus:id,name,name_en',
                'shelfSection.shelf.storeRoom',
                'receivedBy:id,name',
                'items.bookTitle:id,code,title',
            ])
                ->when($request->input('center_id'), fn ($q, $id) => $q->where('center_id', $id))
                ->orderByDesc('returned_on')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only('center_id'),
            'options' => $this->formOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Bookstore/Returns/Create', [
            'options'  => $this->formOptions(),
            'dispatch' => $request->filled('dispatch')
                ? BookDispatch::with('items.bookTitle:id,code,title')->find($request->integer('dispatch'))
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'book_dispatch_id'          => 'nullable|exists:book_dispatches,id',
            'center_id'                 => 'nullable|exists:centers,id',
            'campus_id'                 => 'nullable|exists:campuses,id',
            'shelf_section_id'          => 'required|exists:shelf_sections,id',
            'returned_on'               => 'required|date',
            'returned_by_name'          => 'nullable|string|max:255',
            'condition_note'            => 'nullable|string',
            'lines'                     => 'required|array|min:1',
            'lines.*.book_title_id'     => 'required|exists:book_titles,id',
            'lines.*.quantity_returned' => 'required|integer|min:0',
            'lines.*.quantity_damaged'  => 'nullable|integer|min:0',
            'lines.*.remark'            => 'nullable|string|max:255',
        ]);

        if (blank($validated['center_id']) && blank($validated['campus_id'])) {
            return back()->withErrors(['center_id' => 'Say which centre or campus the books came back from.']);
        }

        try {
            $return = $this->returns->record($validated, $validated['lines'], $request->user());
        } catch (WorkflowException|\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('bookstore.returns.index')
            ->with('success', "Return {$return->return_number} recorded and stock re-shelved.");
    }

    /** @return array<string, mixed> */
    protected function formOptions(): array
    {
        return [
            'centers'  => Center::active()->orderBy('name')->get(['id', 'name', 'code']),
            'campuses' => Campus::orderBy('name')->get(['id', 'name', 'name_en']),
            'titles'   => BookTitle::active()->orderBy('title')->get(['id', 'code', 'title']),
            'sections' => ShelfSection::with('shelf.storeRoom')->ordered()->get()
                ->map(fn (ShelfSection $s) => ['id' => $s->id, 'label' => $s->path]),
        ];
    }
}
