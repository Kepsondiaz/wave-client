<?php

namespace Alal\WaveClient\Exceptions;

/**
 * Thrown by Authenticator::login() after PIN verification succeeds and
 * an SMS code has been dispatched to the user's phone.
 * The caller must collect the code and call confirmSms($code).
 */
class OtpRequiredException extends WaveRequestException
{
    public function __construct(
        public readonly string $tokenId,
        public readonly ?string $mobile = null,
    ) {
        parent::__construct(
            'SMS code required. Call Wave::auth()->confirmSms($code) with the code sent to ' . ($mobile ?? 'your phone') . '.'
        );
    }
}
