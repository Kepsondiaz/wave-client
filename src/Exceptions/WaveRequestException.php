<?php

namespace Alal\WaveClient\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class WaveRequestException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?Response $response = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromResponse(Response $response): static
    {
        $body = $response->json() ?? $response->body();
        $message = is_array($body)
            ? ($body['message'] ?? $body['error'] ?? json_encode($body))
            : (string) $body;

        return new static(
            "Wave request failed [{$response->status()}]: {$message}",
            $response,
            $response->status(),
        );
    }
}
