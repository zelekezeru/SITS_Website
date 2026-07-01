<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Chapa (https://chapa.co) — Ethiopia's leading payment aggregator. A single
 * Chapa checkout lets patrons pay with Telebirr, CBE Birr, cards and more, so
 * this one gateway covers the "Telebirr / Chapa" local rails.
 */
class ChapaGateway implements PaymentGateway
{
    private string $base = 'https://api.chapa.co/v1';

    public function isOnline(): bool
    {
        return ! empty(config('services.chapa.secret'));
    }

    public function method(): string
    {
        return 'chapa';
    }

    public function initiate(Payment $payment): ?string
    {
        if (! $this->isOnline()) {
            return null;
        }

        $payment->loadMissing('user');

        $response = Http::withToken(config('services.chapa.secret'))
            ->acceptJson()
            ->post("{$this->base}/transaction/initialize", [
                'amount'       => (string) $payment->amount,
                'currency'     => $payment->currency,
                'email'        => $payment->user->email,
                'first_name'   => $payment->user->name,
                'tx_ref'       => $payment->reference,
                'callback_url' => route('payments.callback'),
                'return_url'   => route('my.fines'),
                'customization' => [
                    'title' => 'SITS Library fine',
                ],
            ]);

        if ($response->successful() && data_get($response->json(), 'status') === 'success') {
            return data_get($response->json(), 'data.checkout_url');
        }

        Log::warning('Chapa initialize failed', ['body' => $response->body()]);

        return null;
    }

    public function verify(string $reference): bool
    {
        if (! $this->isOnline()) {
            return false;
        }

        $response = Http::withToken(config('services.chapa.secret'))
            ->acceptJson()
            ->get("{$this->base}/transaction/verify/{$reference}");

        return $response->successful()
            && data_get($response->json(), 'data.status') === 'success';
    }
}
