<?php

namespace Alal\WaveClient\Testing;

use Alal\WaveClient\Data\BalanceData;
use Alal\WaveClient\Data\PaymentRequestData;
use Alal\WaveClient\Data\PayoutData;
use Alal\WaveClient\Data\TransactionData;
use Alal\WaveClient\Contracts\WaveClient;
use PHPUnit\Framework\Assert;

/**
 * Drop-in replacement for WaveManager used in tests.
 *
 *   $fake = Wave::fake();
 *   $fake->queueBalance(['balance' => '5000', 'currency' => 'XOF']);
 *   $data = Wave::balance()->get();
 *   $fake->assertSent('balance.get');
 */
class WaveFake implements WaveClient
{
    private array $queue = [];
    private array $recorded = [];

    public function __construct()
    {
        // Skip parent constructor — no real connector needed in fake.
    }

    // ── Queue canned responses ────────────────────────────────────────────

    public function queueBalance(array $data): static
    {
        $this->queue['balance.get'][] = $data;
        return $this;
    }

    public function queuePayout(array $data): static
    {
        $this->queue['payout.send'][] = $data;
        return $this;
    }

    public function queueTransactions(array $items): static
    {
        $this->queue['transactions.list'][] = $items;
        return $this;
    }

    public function queuePaymentRequest(array $data): static
    {
        $this->queue['paymentRequest.create'][] = $data;
        return $this;
    }

    // ── Action proxies — return queued data ──────────────────────────────

    public function balance(): FakeBalance
    {
        return new FakeBalance($this, 'balance');
    }

    public function payout(): FakePayout
    {
        return new FakePayout($this, 'payout');
    }

    public function transactions(): FakeTransactions
    {
        return new FakeTransactions($this, 'transactions');
    }

    public function paymentRequest(): FakePaymentRequest
    {
        return new FakePaymentRequest($this, 'paymentRequest');
    }

    public function auth(): FakeAuthenticator
    {
        return new FakeAuthenticator($this);
    }

    // ── Internal helpers used by Fake* classes ───────────────────────────

    public function dequeue(string $key): ?array
    {
        if (empty($this->queue[$key])) {
            return null;
        }
        return array_shift($this->queue[$key]);
    }

    public function record(string $action, array $args = []): void
    {
        $this->recorded[] = compact('action', 'args');
    }

    // ── Assertions ───────────────────────────────────────────────────────

    public function assertSent(string $action, ?callable $callback = null): void
    {
        $calls = array_filter($this->recorded, fn($r) => $r['action'] === $action);

        Assert::assertNotEmpty(
            $calls,
            "Expected Wave action [{$action}] to be called, but it was not."
        );

        if ($callback !== null) {
            $matched = array_filter($calls, fn($r) => $callback($r['args']));
            Assert::assertNotEmpty(
                $matched,
                "Wave action [{$action}] was called but no call matched the given assertion."
            );
        }
    }

    public function assertNotSent(string $action): void
    {
        $calls = array_filter($this->recorded, fn($r) => $r['action'] === $action);
        Assert::assertEmpty($calls, "Unexpected Wave action [{$action}] was called.");
    }

    public function assertNothingSent(): void
    {
        Assert::assertEmpty($this->recorded, 'Unexpected Wave actions were sent: ' . implode(', ', array_column($this->recorded, 'action')));
    }
}

// ── Inner fake action classes ─────────────────────────────────────────────

class FakeBalance
{
    public function __construct(
        private readonly WaveFake $fake,
        private readonly string $prefix,
    ) {}

    public function get(): BalanceData
    {
        $this->fake->record("{$this->prefix}.get");
        $data = $this->fake->dequeue("{$this->prefix}.get") ?? ['balance' => '0', 'currency' => 'XOF'];
        return BalanceData::from($data);
    }
}

class FakePayout
{
    public function __construct(
        private readonly WaveFake $fake,
        private readonly string $prefix,
    ) {}

    public function send(string $mobile, string $amount, array $options = []): PayoutData
    {
        $this->fake->record("{$this->prefix}.send", compact('mobile', 'amount', 'options'));
        $data = $this->fake->dequeue("{$this->prefix}.send") ?? [
            'id' => 'fake-payout-' . uniqid(),
            'status' => 'processing',
            'amount' => $amount,
            'currency' => 'XOF',
            'mobile' => $mobile,
        ];
        return PayoutData::from($data);
    }

    public function find(string $id): PayoutData
    {
        $this->fake->record("{$this->prefix}.find", compact('id'));
        $data = $this->fake->dequeue("{$this->prefix}.find") ?? ['id' => $id, 'status' => 'succeeded', 'amount' => '0', 'currency' => 'XOF', 'mobile' => ''];
        return PayoutData::from($data);
    }

    public function search(array $filters = []): array
    {
        $this->fake->record("{$this->prefix}.search", $filters);
        return [];
    }
}

class FakeTransactions
{
    public function __construct(
        private readonly WaveFake $fake,
        private readonly string $prefix,
    ) {}

    public function list(array $filters = []): array
    {
        $this->fake->record("{$this->prefix}.list", $filters);
        $items = $this->fake->dequeue("{$this->prefix}.list") ?? [];
        return TransactionData::collection($items);
    }

    public function find(string $id): TransactionData
    {
        $this->fake->record("{$this->prefix}.find", compact('id'));
        $data = $this->fake->dequeue("{$this->prefix}.find") ?? ['id' => $id, 'type' => 'unknown', 'amount' => '0', 'currency' => 'XOF', 'status' => 'unknown'];
        return TransactionData::from($data);
    }
}

class FakePaymentRequest
{
    public function __construct(
        private readonly WaveFake $fake,
        private readonly string $prefix,
    ) {}

    public function create(array $params): PaymentRequestData
    {
        $this->fake->record("{$this->prefix}.create", $params);
        $data = $this->fake->dequeue("{$this->prefix}.create") ?? [
            'id' => 'fake-pr-' . uniqid(),
            'status' => 'pending',
            'amount' => $params['amount'] ?? '0',
            'currency' => $params['currency'] ?? 'XOF',
        ];
        return PaymentRequestData::from($data);
    }

    public function find(string $id): PaymentRequestData
    {
        $this->fake->record("{$this->prefix}.find", compact('id'));
        $data = $this->fake->dequeue("{$this->prefix}.find") ?? ['id' => $id, 'status' => 'pending', 'amount' => '0', 'currency' => 'XOF'];
        return PaymentRequestData::from($data);
    }

    public function cancel(string $id): PaymentRequestData
    {
        $this->fake->record("{$this->prefix}.cancel", compact('id'));
        $data = $this->fake->dequeue("{$this->prefix}.cancel") ?? ['id' => $id, 'status' => 'cancelled', 'amount' => '0', 'currency' => 'XOF'];
        return PaymentRequestData::from($data);
    }
}

class FakeAuthenticator
{
    public function __construct(private readonly WaveFake $fake) {}

    public function login(string $phone, string $pin): void
    {
        $this->fake->record('auth.login', compact('phone', 'pin'));
    }

    public function confirmSms(string $code): void
    {
        $this->fake->record('auth.confirmSms', compact('code'));
    }

    public function logout(): void
    {
        $this->fake->record('auth.logout');
    }
}
