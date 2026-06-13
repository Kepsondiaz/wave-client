<?php

namespace Alal\WaveClient\Data;

class PaymentRequestData
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly string $amount,
        public readonly string $currency,
        public readonly ?string $paymentUrl = null,
        public readonly ?string $reference = null,
        public readonly ?string $expiresAt = null,
        public readonly ?string $createdAt = null,
        public readonly array $raw = [],
    ) {}

    public static function from(array $data): static
    {
        // STUB: map the real response fields here once you have the captured response.
        return new static(
            id:         (string) ($data['id'] ?? ''),
            status:     (string) ($data['status'] ?? 'pending'),
            amount:     (string) ($data['amount'] ?? ''),
            currency:   (string) ($data['currency'] ?? 'XOF'),
            paymentUrl: $data['payment_url'] ?? $data['wave_launch_url'] ?? null,
            reference:  $data['client_reference'] ?? $data['reference'] ?? null,
            expiresAt:  $data['expires_at'] ?? $data['when_expires'] ?? null,
            createdAt:  $data['created_at'] ?? $data['when_created'] ?? null,
            raw:        $data,
        );
    }
}
