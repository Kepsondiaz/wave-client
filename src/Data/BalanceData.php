<?php

namespace Alal\WaveClient\Data;

class BalanceData
{
    public function __construct(
        public readonly string $amount,
        public readonly string $currency,
        public readonly ?string $walletId = null,
        public readonly ?string $updatedAt = null,
        public readonly array $raw = [],
    ) {}

    public static function from(array $data): static
    {
        // STUB: map the real response fields here once you have the captured response.
        return new static(
            amount:    (string) ($data['balance'] ?? $data['amount'] ?? '0'),
            currency:  (string) ($data['currency'] ?? 'XOF'),
            walletId:  $data['wallet_id'] ?? $data['id'] ?? null,
            updatedAt: $data['updated_at'] ?? $data['timestamp'] ?? null,
            raw:       $data,
        );
    }
}
