<?php

namespace Alal\WaveClient\Exceptions;

use Illuminate\Http\Client\Response;

class RateLimitException extends WaveRequestException
{
    public static function fromResponse(Response $response): static
    {
        $retryAfter = $response->header('Retry-After');
        $hint = $retryAfter ? " Retry after {$retryAfter}s." : '';

        return new static("Wave rate limit exceeded.{$hint}", $response, 429);
    }
}
