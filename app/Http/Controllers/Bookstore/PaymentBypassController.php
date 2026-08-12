<?php

namespace App\Http\Controllers\Bookstore;

use App\Enums\PaymentBypassStatus;
use App\Http\Controllers\Controller;
use App\Models\BookPaymentBypass;
use App\Models\BookRequest;
use App\Services\Bookstore\PaymentBypassService;
use App\Services\Bookstore\WorkflowException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Pay-later deferrals: Finance asks, an authoriser accepts the debt. */
class PaymentBypassController extends Controller
{
    public function __construct(private readonly PaymentBypassService $bypasses)
    {
    }

    /** The deferral register — what was released unpaid, and what is still owed. */
    public function index(Request $request): Response
    {
        $bypasses = BookPaymentBypass::query()
            ->with([
                'bookRequest:id,request_number,center_id,campus_id,total_amount',
                'bookRequest.center:id,name',
                'bookRequest.campus:id,name,name_en',
                'requestedBy:id,name',
                'decidedBy:id,name',
            ])
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderByDesc('requested_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Bookstore/Bypasses/Index', [
            'bypasses' => $bypasses,
            'filters'  => $request->only('status'),
            'statuses' => PaymentBypassStatus::options(),
            'totals'   => [
                'pending'     => BookPaymentBypass::pending()->count(),
                'outstanding' => (float) BookPaymentBypass::outstanding()->sum('amount'),
                'overdue'     => BookPaymentBypass::outstanding()
                    ->whereNotNull('promised_on')
                    ->whereDate('promised_on', '<', now())
                    ->count(),
            ],
        ]);
    }

    public function store(Request $request, BookRequest $bookRequest): RedirectResponse
    {
        $validated = $request->validate([
            'reason'      => 'required|string|max:2000',
            'promised_on' => 'nullable|date|after_or_equal:today',
        ]);

        try {
            $bypass = $this->bypasses->request(
                $bookRequest,
                $request->user(),
                $validated['reason'],
                $validated['promised_on'] ?? null,
            );
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Deferral {$bypass->reference} sent for authorisation.");
    }

    public function approve(Request $request, BookPaymentBypass $bypass): RedirectResponse
    {
        $validated = $request->validate(['justification' => 'required|string|max:2000']);

        try {
            $this->bypasses->approve($bypass, $request->user(), $validated['justification']);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Deferral authorised. The amount stays outstanding until it is settled.');
    }

    public function reject(Request $request, BookPaymentBypass $bypass): RedirectResponse
    {
        $validated = $request->validate(['reason' => 'required|string|max:2000']);

        try {
            $this->bypasses->reject($bypass, $request->user(), $validated['reason']);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Deferral declined. The request stays at the payment gate.');
    }

    public function settle(Request $request, BookPaymentBypass $bypass): RedirectResponse
    {
        try {
            $this->bypasses->settle($bypass, $request->user());
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Deferred payment settled.');
    }
}
