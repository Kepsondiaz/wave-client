# wave-client

A Laravel package that automates the **Wave Business** dashboard by replaying its internal GraphQL requests — letting you fetch transaction history, send payouts, check balances, and more from your Laravel application without any manual browser interaction.

> **Note:** This package mimics the private HTTP requests made by the `business.wave.com` web application. It is not based on the official `api.wave.com` REST API. Use responsibly and in accordance with Wave's terms of service.

---

## Requirements

- PHP **8.2+**
- Laravel **11, 12, or 13**
- A **Wave Business** account with API/dashboard access

---

## Installation

### From Packagist (once published)

```bash
composer require alal/wave-client
```

### Local development (path repository)

Add the path repository to your app's `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "../wave-client",
        "options": { "symlink": true }
    }
]
```

Then require it:

```bash
composer require alal/wave-client:@dev
```

The package auto-discovers its service provider and `Wave` facade — no manual registration needed.

---

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=wave-client-config
```

This creates `config/wave-client.php`. Configure your credentials in `.env`:

```env
# GraphQL endpoint — country-specific prefix (ci = Côte d'Ivoire, sn = Senegal…)
WAVE_API_URL=https://ci.mmapp.wave.com/a/business_graphql

# Public dashboard origin (keep as-is)
WAVE_BASE_URL=https://business.wave.com

# Your Wave Business credentials
WAVE_PHONE=+2250700000000
WAVE_PIN=0000

# A stable UUID identifying this "device" — generate once and keep it fixed
WAVE_DEVICE_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx

# Session cache settings (optional)
WAVE_SESSION_STORE=file     # any Laravel cache driver
WAVE_SESSION_KEY=wave-client:session
WAVE_SESSION_TTL=3600       # seconds
```

**Generating a device ID:**

```bash
php artisan tinker --execute "echo \Illuminate\Support\Str::uuid();"
```

Set the output as `WAVE_DEVICE_ID` and never change it — Wave uses it to recognise your device across sessions.

---

## Authentication

Wave Business uses a **three-step login flow**: phone → PIN → SMS code.

### Step 1 — Initiate login

```php
use Alal\WaveClient\Exceptions\OtpRequiredException;

try {
    Wave::auth()->login(
        mobile: '+2250700000000',
        pin:    '0000',
    );
} catch (OtpRequiredException $e) {
    // An SMS code has been sent to $e->mobile
    // Store $e->tokenId if you need it; the package caches it automatically
    echo "Code sent to {$e->mobile}";
}
```

`login()` always throws `OtpRequiredException` on success — it is the signal that the SMS was dispatched and you must collect the code.

### Step 2 — Confirm the SMS code

```php
Wave::auth()->confirmSms('123456');
// Session is now cached — subsequent calls are authenticated automatically
```

### Via Artisan (interactive terminal)

```bash
php artisan wave:login
```

The command prompts for phone and PIN (if not set in `.env`), then asks for the SMS code interactively.

### Session persistence

The authenticated session (`sId`, `walletId`, `businessId`) is stored in your Laravel cache and reused across requests until it expires (`WAVE_SESSION_TTL`). You do not need to log in on every request.

If the session expires, actions throw `AuthenticationException` — catch it to trigger a re-login flow:

```php
use Alal\WaveClient\Exceptions\AuthenticationException;
use Alal\WaveClient\Exceptions\OtpRequiredException;

try {
    $txns = Wave::transactions()->list();
} catch (AuthenticationException $e) {
    // Session expired — re-trigger your login flow
}
```

### Logout

```php
Wave::auth()->logout(); // clears the cached session
```

---

## Usage

### Transactions

```php
// Current month (default)
$transactions = Wave::transactions()->list();

// Custom date range and filters
$transactions = Wave::transactions()->list([
    'start'            => '2026-06-01',
    'end'              => '2026-06-30',
    'limit'            => 50,
    'transactionType'  => 'ALL',        // 'ALL' | 'RECEIVED' | 'SENT'
    'searchTerm'       => null,
    'customerMobileStr'=> '+2250700000000',
    'includePending'   => true,
]);

// Each item is a TransactionData object
foreach ($transactions as $txn) {
    echo $txn->type;            // e.g. MerchantSaleEntry, PayoutTransferEntry…
    echo $txn->amount;          // e.g. "5000"
    echo $txn->summary;         // human-readable label
    echo $txn->date;            // whenEntered ISO 8601
    echo $txn->mobile;          // counterparty phone (normalised across types)
    echo $txn->name;            // counterparty name
    echo $txn->isPending;       // bool
    echo $txn->isCancelled;     // bool
    echo $txn->grossAmount;     // before fees (MerchantSaleEntry, PayoutTransferEntry)
    echo $txn->feeAmount;       // fee charged
    echo $txn->clientReference; // your reference (MerchantSaleEntry)
    // $txn->raw — full raw GraphQL response array for this entry
}

// Find a single transaction by ID
$txn = Wave::transactions()->find('TXN_ID');
```

### Balance *(stub — wire your captured request)*

```php
$balance = Wave::balance()->get();

echo $balance->amount;   // e.g. "125000"
echo $balance->currency; // e.g. "XOF"
```

### Payout *(stub — wire your captured request)*

```php
$payout = Wave::payout()->send(
    mobile:  '+2250700000000',
    amount:  '5000',
    options: [
        'name'      => 'John Doe',
        'reference' => 'INV-2026-001',
        'reason'    => 'Salary June',
    ]
);

echo $payout->id;
echo $payout->status; // processing | succeeded | failed
```

### Payment Request *(stub — wire your captured request)*

```php
$request = Wave::paymentRequest()->create([
    'amount'      => '10000',
    'currency'    => 'XOF',
    'reference'   => 'ORDER-42',
    'description' => 'Payment for order #42',
]);

echo $request->paymentUrl; // share this link with your customer
echo $request->status;     // pending | completed | cancelled
```

---

## Exception Reference

| Exception | When thrown |
|---|---|
| `OtpRequiredException` | `login()` succeeded — SMS was sent. Carry on with `confirmSms()`. |
| `AuthenticationException` | No valid session exists, or the session expired. Re-run the login flow. |
| `WaveRequestException` | The HTTP request failed (non-2xx response or GraphQL error field). |
| `RateLimitException` | HTTP 429 — too many requests. Check `$e->getMessage()` for retry hint. |

All exceptions extend `WaveRequestException`, which exposes the raw `$e->response` (an `Illuminate\Http\Client\Response`) for debugging.

---

## Testing

The package ships a `WaveFake` that swaps out the real client in tests — no network calls, no OTP, no credentials needed.

### Basic usage

```php
use Alal\WaveClient\Facades\Wave;

// In your test
$fake = Wave::fake();
```

### Queueing canned responses

```php
$fake->queueBalance([
    'balance'  => '50000',
    'currency' => 'XOF',
]);

$fake->queuePayout([
    'id'       => 'payout-001',
    'status'   => 'processing',
    'amount'   => '5000',
    'currency' => 'XOF',
    'mobile'   => '+2250700000000',
]);

$fake->queueTransactions([
    [
        'id'          => 'txn-001',
        '__typename'  => 'MerchantSaleEntry',
        'amount'      => '3000',
        'summary'     => 'Sale',
        'whenEntered' => '2026-06-10T14:00:00Z',
        'isPending'   => false,
        'isCancelled' => false,
    ],
]);

$fake->queuePaymentRequest([
    'id'          => 'pr-001',
    'status'      => 'pending',
    'amount'      => '10000',
    'currency'    => 'XOF',
    'payment_url' => 'https://pay.wave.com/pr-001',
]);
```

If no response is queued for an action, the fake returns a sensible default.

### Assertions

```php
// Assert an action was called (any args)
$fake->assertSent('balance.get');
$fake->assertSent('payout.send');
$fake->assertSent('transactions.list');
$fake->assertSent('paymentRequest.create');

// Assert with argument inspection
$fake->assertSent('payout.send', fn(array $args) =>
    $args['mobile'] === '+2250700000000' &&
    $args['amount'] === '5000'
);

$fake->assertSent('auth.login', fn(array $args) =>
    $args['phone'] === '+2250700000000'
);

// Assert an action was NOT called
$fake->assertNotSent('payout.send');

// Assert nothing at all was sent
$fake->assertNothingSent();
```

### Full test example (Pest)

```php
it('sends a payout when a payment is approved', function () {
    $fake = Wave::fake();
    $fake->queuePayout([
        'id'       => 'p-1',
        'status'   => 'processing',
        'amount'   => '5000',
        'currency' => 'XOF',
        'mobile'   => '+2250700000000',
    ]);

    // Call the service that internally uses Wave::payout()->send(...)
    app(PaymentService::class)->disburse($payment);

    $fake->assertSent('payout.send', fn(array $args) =>
        $args['mobile'] === '+2250700000000'
    );
});
```

### Full test example (PHPUnit)

```php
public function test_balance_is_fetched_on_dashboard(): void
{
    $fake = Wave::fake();
    $fake->queueBalance(['balance' => '25000', 'currency' => 'XOF']);

    $response = $this->get('/dashboard');

    $response->assertSee('25 000 XOF');
    $fake->assertSent('balance.get');
}
```

---

## Available action keys for assertions

| Key | Triggered by |
|---|---|
| `auth.login` | `Wave::auth()->login()` |
| `auth.confirmSms` | `Wave::auth()->confirmSms()` |
| `auth.logout` | `Wave::auth()->logout()` |
| `balance.get` | `Wave::balance()->get()` |
| `payout.send` | `Wave::payout()->send()` |
| `payout.find` | `Wave::payout()->find()` |
| `payout.search` | `Wave::payout()->search()` |
| `transactions.list` | `Wave::transactions()->list()` |
| `transactions.find` | `Wave::transactions()->find()` |
| `paymentRequest.create` | `Wave::paymentRequest()->create()` |
| `paymentRequest.find` | `Wave::paymentRequest()->find()` |
| `paymentRequest.cancel` | `Wave::paymentRequest()->cancel()` |

---

## Wiring a new action from a captured request

1. Open DevTools → Network → copy the request as cURL.
2. Identify the GraphQL query string and variables.
3. Add a `const QUERY` and implement the method in the relevant `src/Actions/` class.
4. Map the response fields in the corresponding `src/Data/` DTO.
5. Add a `queue*` helper and tests in `WaveFake` if needed.

The `WaveConnector::graphql()` method handles auth, headers, error mapping, and trace context automatically.

---

## Running the package tests

```bash
cd wave-client
composer install
composer test
```

---

## License

MIT — see [LICENSE](LICENSE).
