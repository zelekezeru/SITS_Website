<?php

namespace App\Services\Payments;

use App\Models\Payment;

interface PaymentGateway
{
    /** Whether this gateway can take payments online (vs. recorded at a desk). */
    public function isOnline(): bool;

    /** The payment method label stored on the Payment record. */
    public function method(): string;

    /**
     * Begin an online payment. Returns a checkout URL to redirect the patron
     * to, or null when the gateway cannot start an online session.
     */
    public function initiate(Payment $payment): ?string;

    /** Verify a completed transaction by its reference (tx_ref). */
    public function verify(string $reference): bool;
}
