<?php

namespace App\Http\Controllers\Bookstore;

use App\Enums\BookRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\BookDispatch;
use App\Models\BookRequest;
use App\Models\BookStock;
use App\Services\Bookstore\DispatchService;
use App\Services\Bookstore\QrLabelService;
use App\Services\Bookstore\WorkflowException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Releasing approved books out of the store, and the waybill that goes with them. */
class BookDispatchController extends Controller
{
    public function __construct(
        private readonly DispatchService $dispatcher,
        private readonly QrLabelService $qr,
    ) {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Bookstore/Dispatches/Index', [
            'dispatches' => BookDispatch::with([
                'bookRequest:id,request_number,center_id,campus_id,destination_type',
                'bookRequest.center:id,name',
                'bookRequest.campus:id,name,name_en',
                'dispatchedBy:id,name',
            ])
                ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
                ->orderByDesc('dispatched_at')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only('status'),
            'pending' => BookRequest::whereIn('status', [
                BookRequestStatus::APPROVED->value,
                BookRequestStatus::PARTIALLY_DISPATCHED->value,
            ])->count(),
        ]);
    }

    /** The picking screen: outstanding lines, and where each title can be picked from. */
    public function create(BookRequest $bookRequest): Response
    {
        abort_unless(
            in_array($bookRequest->status, [BookRequestStatus::APPROVED, BookRequestStatus::PARTIALLY_DISPATCHED], true),
            403,
            'Only an approved request can be dispatched.'
        );

        $bookRequest->load(['items.bookTitle', 'center', 'campus']);

        $picking = $bookRequest->items->mapWithKeys(fn ($item) => [
            $item->id => $this->dispatcher->pickingOptions($item->bookTitle)
                ->map(fn (BookStock $stock) => [
                    'shelf_section_id' => $stock->shelf_section_id,
                    'label'            => $stock->shelfSection->path,
                    'quantity'         => $stock->quantity,
                    'available'        => $stock->available,
                ])
                ->values(),
        ]);

        return Inertia::render('Bookstore/Dispatches/Create', [
            'request' => $bookRequest,
            'picking' => $picking,
        ]);
    }

    public function store(Request $request, BookRequest $bookRequest): RedirectResponse
    {
        $validated = $request->validate([
            'lines'                        => 'required|array|min:1',
            'lines.*.book_request_item_id' => 'required|integer',
            'lines.*.shelf_section_id'     => 'required|exists:shelf_sections,id',
            'lines.*.quantity'             => 'required|integer|min:0',
            'received_by_name'             => 'nullable|string|max:255',
            'received_by_phone'            => 'nullable|string|max:30',
            'notes'                        => 'nullable|string',
        ]);

        try {
            $dispatch = $this->dispatcher->dispatch(
                $bookRequest,
                $request->user(),
                $validated['lines'],
                [
                    'received_by_name'  => $validated['received_by_name'] ?? null,
                    'received_by_phone' => $validated['received_by_phone'] ?? null,
                    'notes'             => $validated['notes'] ?? null,
                ]
            );
        } catch (WorkflowException|\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('bookstore.dispatches.show', $dispatch)
            ->with('success', "Waybill {$dispatch->dispatch_number} created. Print it for the receiver to sign.");
    }

    public function show(BookDispatch $dispatch): Response
    {
        $dispatch->load([
            'bookRequest.center',
            'bookRequest.campus:id,name,name_en',
            'bookRequest.requester:id,name',
            'items.bookTitle:id,code,title',
            'items.shelfSection.shelf.storeRoom',
            'dispatchedBy:id,name',
            'receivedByUser:id,name',
        ]);

        return Inertia::render('Bookstore/Dispatches/Show', [
            'dispatch' => $dispatch,
            'qr'       => $this->qr->label($dispatch),
        ]);
    }

    /** The printable handover note ("ንብረት መረካከቢያ ፎርም"), with the waybill QR. */
    public function print(BookDispatch $dispatch): HttpResponse
    {
        $dispatch->load([
            'bookRequest.center',
            'bookRequest.campus:id,name,name_en',
            'items.bookTitle:id,code,title',
            'items.shelfSection.shelf.storeRoom',
            'dispatchedBy:id,name',
        ]);

        $pdf = Pdf::loadView('bookstore.print.dispatch-note', [
            'dispatch' => $dispatch,
            'qr'       => $this->qr->label($dispatch, 200),
        ])->setPaper('a4');

        return $pdf->stream("waybill-{$dispatch->dispatch_number}.pdf");
    }

    /** Confirm delivery — from the waybill QR on a phone, or at the desk. */
    public function confirm(Request $request, BookDispatch $dispatch): RedirectResponse
    {
        $validated = $request->validate([
            'received_by_name'  => 'nullable|string|max:255',
            'received_by_phone' => 'nullable|string|max:30',
        ]);

        $this->dispatcher->confirmReceipt($dispatch, $request->user(), $validated);

        return back()->with('success', 'Delivery confirmed.');
    }
}
