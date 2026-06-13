<?php

namespace Alal\WaveClient\Exceptions;

class AuthenticationException extends WaveRequestException
{
    public static function sessionExpired(): static
    {
        return new static('Wave session expired or missing. Call Wave::auth()->login() first.');
    }
}
