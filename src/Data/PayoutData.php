<?php

namespace Alal\WaveClient\Data;

class PayoutData
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $mobile,
        public readonly ?string $fee = null,
        public readonly ?string $reference = null,
        public readonly ?string $createdAt = null,
        public readonly array $raw = [],
    ) {}

    public static function from(array $data): static
    {
        // STUB: map the real response fields here once you have the captured response.
        return new static(
            id:        (string) ($data['id'] ?? $data['transaction_id'] ?? ''),
            status:    (string) ($data['status'] ?? 'processing'),
            amount:    (string) ($data['amount'] ?? $data['receive_amount'] ?? ''),
            currency:  (string) ($data['currency'] ?? 'XOF'),
            mobile:    (string) ($data['mobile'] ?? $data['recipient_mobile'] ?? ''),
            fee:       isset($data['fee']) ? (string) $data['fee'] : null,
            reference: $data['client_reference'] ?? $data['reference'] ?? null,
            createdAt: $data['created_at'] ?? $data['timestamp'] ?? null,
            raw:       $data,
        );
    }
}
