<?php

namespace App\Http\Controllers\Bookstore;

use App\Enums\BookPaymentMethod;
use App\Enums\BookPaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\BookPayment;
use App\Models\BookRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Money against book requests.
 *
 * Two references are captured deliberately: the bank/wallet TRANSACTION
 * REFERENCE proves the money moved, and the manual CRV NUMBER ties the record
 * back to the paper receipt book finance still keeps. Receipt images live on the
 * private disk and are streamed through {@see self::receipt()} — never a public
 * URL, because they carry account numbers.
 */
class BookPaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $payments = BookPayment::query()
            ->with(['bookRequest:id,request_number,total_amount,center_id,campus_id', 'recordedBy:id,name', 'verifiedBy:id,name'])
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('search'), fn ($q, $term) => $q->where(fn ($sub) => $sub
                ->where('transaction_reference', 'like', "%{$term}%")
                ->orWhere('crv_number', 'like', "%{$term}%")
                ->orWhere('receipt_number', 'like', "%{$term}%")))
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Bookstore/Payments/Index', [
            'payments' => $payments,
            'filters'  => $request->only(['status', 'search']),
            'statuses' => BookPaymentStatus::options(),
            'totals'   => [
                'pending'  => (float) BookPayment::where('status', BookPaymentStatus::PENDING->value)->sum('amount'),
                'verified' => (float) BookPayment::where('status', BookPaymentStatus::VERIFIED->value)->sum('amount'),
            ],
        ]);
    }

    public function store(Request $request, BookRequest $bookRequest): RedirectResponse
    {
        $validated = $request->validate([
            'amount'                => 'required|numeric|min:0',
            'method'                => 'required|string|in:'.implode(',', array_column(BookPaymentMethod::cases(), 'value')),
            'bank_name'             => 'nullable|string|max:255',
            'transaction_reference' => 'nullable|string|max:80',
            'crv_number'            => 'nullable|string|max:60',
            'receipt_number'        => 'nullable|string|max:60',
            'paid_on'               => 'required|date',
            'receipt_image'         => 'nullable|image|max:5120',
            'notes'                 => 'nullable|string',
        ]);

        $method = BookPaymentMethod::from($validated['method']);

        if ($method->requiresTransactionReference() && blank($validated['transaction_reference'] ?? null)) {
            return back()->withErrors([
                'transaction_reference' => 'A '.$method->label().' payment must carry its bank or wallet transaction reference.',
            ]);
        }

        $path = $request->hasFile('receipt_image')
            ? $request->file('receipt_image')->store("bookstore/receipts/{$bookRequest->id}", 'local')
            : null;

        $bookRequest->payments()->create([
            ...$validated,
            'receipt_image_path' => $path,
            'status'             => BookPaymentStatus::PENDING,
            'recorded_by'        => $request->user()->id,
        ]);

        return back()->with('success', 'Payment recorded. Finance will verify it against the bank record.');
    }

    public function verify(Request $request, BookPayment $payment): RedirectResponse
    {
        if (! $payment->isPending()) {
            return back()->with('error', 'This payment has already been decided.');
        }

        $validated = $request->validate(['note' => 'nullable|string|max:1000']);

        $payment->verify($request->user(), $validated['note'] ?? null);

        return back()->with('success', 'Payment verified.');
    }

    public function reject(Request $request, BookPayment $payment): RedirectResponse
    {
        if (! $payment->isPending()) {
            return back()->with('error', 'This payment has already been decided.');
        }

        $validated = $request->validate(['reason' => 'required|string|max:1000']);

        $payment->reject($request->user(), $validated['reason']);

        return back()->with('success', 'Payment rejected.');
    }

    /** Stream the scanned receipt from the private disk. */
    public function receipt(BookPayment $payment): StreamedResponse
    {
        abort_unless($payment->hasReceiptImage(), 404);
        abort_unless(Storage::disk('local')->exists($payment->receipt_image_path), 404);

        return Storage::disk('local')->response(
            $payment->receipt_image_path,
            'receipt-'.($payment->crv_number ?: $payment->id).'.jpg',
            ['Content-Disposition' => 'inline']
        );
    }
}
