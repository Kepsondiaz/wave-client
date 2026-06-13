<?php

namespace Alal\WaveClient;

use Alal\WaveClient\Actions\Balance;
use Alal\WaveClient\Actions\PaymentRequest;
use Alal\WaveClient\Actions\Payout;
use Alal\WaveClient\Actions\Transactions;
use Alal\WaveClient\Auth\Authenticator;
use Alal\WaveClient\Contracts\SessionStore;
use Alal\WaveClient\Contracts\WaveClient;
use Alal\WaveClient\Exceptions\AuthenticationException;
use Alal\WaveClient\Http\WaveConnector;
use Alal\WaveClient\Http\WaveSession;
use Illuminate\Support\Str;

class WaveManager implements WaveClient
{
    private WaveConnector $connector;

    public function __construct(
        private readonly array $config,
        private readonly SessionStore $sessionStore,
    ) {
        $session = $this->sessionStore->get() ?? new WaveSession();
        $this->connector = new WaveConnector($config, $session);
    }

    public function auth(): Authenticator
    {
        return new Authenticator(
            connector: $this->connector,
            store:     $this->sessionStore,
            deviceId:  $this->resolveDeviceId(),
        );
    }

    public function balance(): Balance
    {
        $this->requireSession();
        return new Balance($this->connector);
    }

    public function payout(): Payout
    {
        $this->requireSession();
        return new Payout($this->connector);
    }

    public function transactions(): Transactions
    {
        $this->requireSession();
        return new Transactions($this->connector);
    }

    public function paymentRequest(): PaymentRequest
    {
        $this->requireSession();
        return new PaymentRequest($this->connector);
    }

    public function connector(): WaveConnector
    {
        return $this->connector;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function requireSession(): void
    {
        $session = $this->sessionStore->get();

        if (!$session || !$session->isAuthenticated()) {
            throw AuthenticationException::sessionExpired();
        }

        $this->connector->setSession($session);
    }

    private function resolveDeviceId(): string
    {
        $deviceId = $this->config['device_id'] ?? null;

        if (!$deviceId) {
            // Generate a stable device ID and persist it in the session store.
            // On next call the config should be set via WAVE_DEVICE_ID.
            $deviceId = (string) Str::uuid();
        }

        return $deviceId;
    }
}
