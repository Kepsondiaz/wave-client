<?php

namespace Alal\WaveClient\Data;

class TransactionData
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $type,        // __typename
        public readonly string  $amount,
        public readonly string  $summary,
        public readonly string  $date,        // whenEntered
        public readonly bool    $isPending,
        public readonly bool    $isCancelled,
        // Common optional counterparty fields (populated by inline fragments)
        public readonly ?string $mobile = null,
        public readonly ?string $name = null,
        public readonly ?string $transferId = null,
        public readonly ?string $clientReference = null,
        public readonly ?string $grossAmount = null,
        public readonly ?string $feeAmount = null,
        public readonly ?bool   $isRefunded = null,
        public readonly ?bool   $isReversal = null,
        public readonly array   $raw = [],
    ) {}

    public static function from(array $data): static
    {
        $type = $data['__typename'] ?? 'Unknown';

        // Normalise counterparty mobile + name across all entry types.
        $mobile = $data['customerMobile']
            ?? $data['recipientMobile']
            ?? $data['senderMobile']
            ?? null;

        $name = $data['customerName']
            ?? $data['recipientName']
            ?? $data['maybeRecipientName']
            ?? $data['senderName']
            ?? $data['agentName']
            ?? null;

        $transferId = $data['transferId']
            ?? $data['transferOpaqueId']
            ?? $data['tcid']
            ?? null;

        return new static(
            id:              (string) ($data['id'] ?? ''),
            type:            $type,
            amount:          (string) ($data['amount'] ?? '0'),
            summary:         (string) ($data['summary'] ?? ''),
            date:            (string) ($data['whenEntered'] ?? ''),
            isPending:       (bool)   ($data['isPending'] ?? false),
            isCancelled:     (bool)   ($data['isCancelled'] ?? false),
            mobile:          $mobile,
            name:            $name,
            transferId:      $transferId,
            clientReference: $data['clientReference'] ?? null,
            grossAmount:     isset($data['grossAmount']) ? (string) $data['grossAmount'] : null,
            feeAmount:       isset($data['feeAmount'])   ? (string) $data['feeAmount']   : null,
            isRefunded:      $data['isRefunded'] ?? null,
            isReversal:      $data['isReversal'] ?? null,
            raw:             $data,
        );
    }

    public static function collection(array $items): array
    {
        return array_map(fn(array $item) => static::from($item), $items);
    }
}
