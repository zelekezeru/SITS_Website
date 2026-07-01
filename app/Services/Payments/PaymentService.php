<?php

namespace App\Services\Payments;

use App\Models\Fine;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    /** Resolve the active gateway from config. */
    public function gateway(): PaymentGateway
    {
        return match (config('library.payment.driver')) {
            'chapa', 'telebirr' => app(ChapaGateway::class),
            default             => app(ManualGateway::class),
        };
    }

    public function isOnlineAvailable(): bool
    {
        return $this->gateway()->isOnline();
    }

    /**
     * Start an online payment for a fine and return the checkout URL.
     */
    public function startOnline(Fine $fine, User $user): string
    {
        abort_if($fine->balance <= 0, 422, 'This fine is already settled.');

        $gateway = $this->gateway();
        abort_unless($gateway->isOnline(), 422, 'Online payment is not available right now.');

        $payment = Payment::create([
            'fine_id'   => $fine->id,
            'user_id'   => $fine->user_id,
            'amount'    => $fine->balance,
            'currency'  => config('library.currency'),
            'method'    => $gateway->method(),
            'status'    => 'pending',
            'reference' => 'LIBFINE-' . $fine->id . '-' . Str::upper(Str::random(10)),
        ]);

        $url = $gateway->initiate($payment);
        abort_unless($url, 422, 'Could not start the payment session. Please try again.');

        return $url;
    }

    /**
     * Verify a pending online payment by reference and, if successful, settle it.
     */
    public function confirm(string $reference): bool
    {
        $payment = Payment::where('reference', $reference)->first();
        if (! $payment || $payment->status === 'success') {
            return (bool) $payment;
        }

        if (! $this->gateway()->verify($reference)) {
            $payment->update(['status' => 'failed']);
            return false;
        }

        $this->settle($payment);

        return true;
    }

    /**
     * Record a payment taken in person at the desk.
     */
    public function recordManual(Fine $fine, float $amount, User $actor, string $method = 'cash'): Payment
    {
        abort_if($amount <= 0, 422, 'Payment amount must be positive.');
        abort_if($amount > $fine->balance + 0.001, 422, 'Payment exceeds the outstanding balance.');

        $payment = Payment::create([
            'fine_id'     => $fine->id,
            'user_id'     => $fine->user_id,
            'recorded_by' => $actor->id,
            'amount'      => $amount,
            'currency'    => config('library.currency'),
            'method'      => $method,
            'status'      => 'success',
            'reference'   => 'LIBDESK-' . $fine->id . '-' . Str::upper(Str::random(10)),
            'paid_at'     => now(),
        ]);

        $this->applyToFine($payment);

        return $payment;
    }

    /** Mark an online payment successful and apply it to its fine. */
    protected function settle(Payment $payment): void
    {
        $payment->update(['status' => 'success', 'paid_at' => now()]);
        $this->applyToFine($payment);
    }

    /** Increment the fine's paid amount and close it when fully paid. */
    protected function applyToFine(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $fine = $payment->fine()->lockForUpdate()->first();

            $paid = round((float) $fine->paid_amount + (float) $payment->amount, 2);
            $fine->update([
                'paid_amount' => $paid,
                'status'      => $paid >= (float) $fine->amount ? 'paid' : 'open',
            ]);
        });
    }
}
