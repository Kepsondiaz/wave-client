<?php

namespace Alal\WaveClient\Http;

/**
 * Holds the state that accumulates across the three-step Wave auth flow:
 *   1. startBusinessUserAuth(phone)       → no new data, triggers flow
 *   2. login(phone, pin, deviceId)        → stores tokenId + pendingPin
 *   3. verifyAuthCode(tokenId, code, pin) → stores sId + business/wallet IDs
 */
class WaveSession
{
    public function __construct(
        private ?string $sId = null,
        private ?string $tokenId = null,
        private ?string $pendingPin = null,
        private ?string $walletId = null,
        private ?string $businessId = null,
        private ?string $userId = null,
    ) {}

    // ── After step 2 (verifyPin) ──────────────────────────────────────────

    public function setPendingVerification(string $tokenId, string $pin): void
    {
        $this->tokenId    = $tokenId;
        $this->pendingPin = $pin;
    }

    public function tokenId(): ?string
    {
        return $this->tokenId;
    }

    public function pendingPin(): ?string
    {
        return $this->pendingPin;
    }

    public function hasPendingVerification(): bool
    {
        return $this->tokenId !== null && $this->pendingPin !== null;
    }

    // ── After step 3 (verifySms) ──────────────────────────────────────────

    public function authenticate(string $sId, ?string $walletId, ?string $businessId, ?string $userId): void
    {
        $this->sId        = $sId;
        $this->walletId   = $walletId;
        $this->businessId = $businessId;
        $this->userId     = $userId;
        $this->tokenId    = null;
        $this->pendingPin = null;
    }

    public function sId(): ?string
    {
        return $this->sId;
    }

    public function walletId(): ?string
    {
        return $this->walletId;
    }

    public function businessId(): ?string
    {
        return $this->businessId;
    }

    public function userId(): ?string
    {
        return $this->userId;
    }

    public function isAuthenticated(): bool
    {
        return $this->sId !== null;
    }

    // ── Serialization ─────────────────────────────────────────────────────

    public function serialize(): array
    {
        return [
            's_id'        => $this->sId,
            'token_id'    => $this->tokenId,
            'pending_pin' => $this->pendingPin,
            'wallet_id'   => $this->walletId,
            'business_id' => $this->businessId,
            'user_id'     => $this->userId,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            sId:        $data['s_id'] ?? null,
            tokenId:    $data['token_id'] ?? null,
            pendingPin: $data['pending_pin'] ?? null,
            walletId:   $data['wallet_id'] ?? null,
            businessId: $data['business_id'] ?? null,
            userId:     $data['user_id'] ?? null,
        );
    }
}
