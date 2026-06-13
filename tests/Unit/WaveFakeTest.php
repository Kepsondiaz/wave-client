<?php

use Alal\WaveClient\Facades\Wave;

it('returns canned balance data', function () {
    $fake = Wave::fake();
    $fake->queueBalance(['balance' => '25000', 'currency' => 'XOF']);

    $data = Wave::balance()->get();

    expect($data->amount)->toBe('25000')
        ->and($data->currency)->toBe('XOF');
});

it('asserts balance was fetched', function () {
    $fake = Wave::fake();
    $fake->queueBalance(['balance' => '1000', 'currency' => 'XOF']);

    Wave::balance()->get();

    $fake->assertSent('balance.get');
});

it('asserts payout was sent with correct args', function () {
    $fake = Wave::fake();
    $fake->queuePayout([
        'id' => 'p-1', 'status' => 'processing',
        'amount' => '5000', 'currency' => 'XOF', 'mobile' => '+2250700000000',
    ]);

    Wave::payout()->send('+2250700000000', '5000');

    $fake->assertSent('payout.send', fn(array $args) =>
        $args['mobile'] === '+2250700000000' && $args['amount'] === '5000'
    );
});

it('asserts nothing sent when no calls made', function () {
    $fake = Wave::fake();
    $fake->assertNothingSent();
});

it('asserts action was NOT sent', function () {
    $fake = Wave::fake();
    $fake->assertNotSent('payout.send');
});

it('records auth login call with phone and pin', function () {
    $fake = Wave::fake();

    Wave::auth()->login('+2250700000000', '0000');

    $fake->assertSent('auth.login', fn(array $args) =>
        $args['phone'] === '+2250700000000' && $args['pin'] === '0000'
    );
});

it('records confirmSms call', function () {
    $fake = Wave::fake();

    Wave::auth()->confirmSms('1234');

    $fake->assertSent('auth.confirmSms', fn(array $args) => $args['code'] === '1234');
});

it('queues and returns multiple transactions', function () {
    $fake = Wave::fake();
    $fake->queueTransactions([
        ['id' => 't-1', 'type' => 'payout', 'amount' => '3000', 'currency' => 'XOF', 'status' => 'succeeded'],
        ['id' => 't-2', 'type' => 'payment', 'amount' => '1500', 'currency' => 'XOF', 'status' => 'succeeded'],
    ]);

    $list = Wave::transactions()->list();

    expect($list)->toHaveCount(2)
        ->and($list[0]->id)->toBe('t-1')
        ->and($list[1]->id)->toBe('t-2');
});

it('queues and returns a payment request', function () {
    $fake = Wave::fake();
    $fake->queuePaymentRequest([
        'id' => 'pr-1', 'status' => 'pending',
        'amount' => '10000', 'currency' => 'XOF',
        'payment_url' => 'https://pay.wave.com/pr-1',
    ]);

    $data = Wave::paymentRequest()->create(['amount' => '10000']);

    expect($data->id)->toBe('pr-1')
        ->and($data->paymentUrl)->toBe('https://pay.wave.com/pr-1');
});
