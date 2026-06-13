<?php

use Alal\WaveClient\Http\WaveSession;

it('starts unauthenticated', function () {
    $session = new WaveSession();
    expect($session->isAuthenticated())->toBeFalse();
    expect($session->hasPendingVerification())->toBeFalse();
});

it('tracks pending verification state after PIN step', function () {
    $session = new WaveSession();
    $session->setPendingVerification('ST_ci_abc123', '0000');

    expect($session->hasPendingVerification())->toBeTrue()
        ->and($session->tokenId())->toBe('ST_ci_abc123')
        ->and($session->pendingPin())->toBe('0000')
        ->and($session->isAuthenticated())->toBeFalse();
});

it('becomes authenticated after SMS step', function () {
    $session = new WaveSession();
    $session->authenticate('sid-abc', 'wallet-1', 'business-1', 'user-1');

    expect($session->isAuthenticated())->toBeTrue()
        ->and($session->sId())->toBe('sid-abc')
        ->and($session->walletId())->toBe('wallet-1')
        ->and($session->businessId())->toBe('business-1')
        ->and($session->hasPendingVerification())->toBeFalse();
});

it('clears pending verification when authenticated', function () {
    $session = new WaveSession();
    $session->setPendingVerification('tok', 'pin');
    $session->authenticate('sid', null, null, null);

    expect($session->tokenId())->toBeNull()
        ->and($session->pendingPin())->toBeNull();
});

it('serializes and deserializes a pending session', function () {
    $session = new WaveSession();
    $session->setPendingVerification('ST_ci_xyz', '1234');

    $restored = WaveSession::fromArray($session->serialize());

    expect($restored->tokenId())->toBe('ST_ci_xyz')
        ->and($restored->pendingPin())->toBe('1234')
        ->and($restored->isAuthenticated())->toBeFalse();
});

it('serializes and deserializes an authenticated session', function () {
    $session = new WaveSession();
    $session->authenticate('sid-99', 'w-1', 'b-1', 'u-1');

    $restored = WaveSession::fromArray($session->serialize());

    expect($restored->sId())->toBe('sid-99')
        ->and($restored->walletId())->toBe('w-1')
        ->and($restored->isAuthenticated())->toBeTrue();
});
