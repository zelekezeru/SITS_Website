<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use App\Models\Fine;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /** Patron starts paying one of their own fines online. */
    public function pay(Request $request, Fine $fine, PaymentService $payments)
    {
        abort_unless($fine->user_id === $request->user()->id, 403);

        $url = $payments->startOnline($fine, $request->user());

        // Full-page redirect out to the gateway's hosted checkout.
        return Inertia::location($url);
    }

    /**
     * Gateway callback / browser return. Verifies the transaction by its
     * reference and settles the fine. Public (server-to-server safe).
     */
    public function callback(Request $request, PaymentService $payments)
    {
        $reference = $request->query('tx_ref')
            ?? $request->query('trx_ref')
            ?? $request->input('tx_ref');

        if ($reference) {
            $payments->confirm($reference);
        }

        // Browsers landing here get sent to their fines page; webhooks get 200.
        if ($request->user()) {
            return redirect()->route('my.fines')->with('success', 'Thanks! Your payment is being confirmed.');
        }

        return response()->json(['ok' => true]);
    }
}
