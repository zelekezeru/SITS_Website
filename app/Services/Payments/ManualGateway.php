<?php

namespace App\Services\Payments;

use App\Models\Payment;

/**
 * Default gateway: payments are collected in person and recorded by staff.
 * No online checkout is available.
 */
class ManualGateway implements PaymentGateway
{
    public function isOnline(): bool
    {
        return false;
    }

    public function method(): string
    {
        return 'cash';
    }

    public function initiate(Payment $payment): ?string
    {
        return null;
    }

    public function verify(string $reference): bool
    {
        return false;
    }
}
