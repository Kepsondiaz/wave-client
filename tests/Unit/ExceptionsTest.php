<?php

use Alal\WaveClient\Exceptions\AuthenticationException;
use Alal\WaveClient\Exceptions\OtpRequiredException;

it('creates session-expired exception', function () {
    $e = AuthenticationException::sessionExpired();
    expect($e->getMessage())->toContain('Wave session expired');
});

it('creates OTP-required exception with mobile hint', function () {
    $e = new OtpRequiredException('ST_ci_abc123', '+2250700000000');

    expect($e->tokenId)->toBe('ST_ci_abc123')
        ->and($e->mobile)->toBe('+2250700000000')
        ->and($e->getMessage())->toContain('+2250700000000')
        ->and($e->getMessage())->toContain('confirmSms');
});
