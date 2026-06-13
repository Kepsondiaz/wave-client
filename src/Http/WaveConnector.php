<?php

namespace Alal\WaveClient\Http;

use Alal\WaveClient\Exceptions\AuthenticationException;
use Alal\WaveClient\Exceptions\RateLimitException;
use Alal\WaveClient\Exceptions\WaveRequestException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WaveConnector
{
    public function __construct(
        private readonly array $config,
        private WaveSession $session,
    ) {}

    public function session(): WaveSession
    {
        return $this->session;
    }

    public function setSession(WaveSession $session): void
    {
        $this->session = $session;
    }

    /**
     * A PendingRequest without auth headers.
     * Used for the unauthenticated steps of the login flow.
     */
    public function publicRequest(): PendingRequest
    {
        return Http::timeout($this->config['http']['timeout'])
            ->withHeaders($this->defaultHeaders());
    }

    /**
     * A PendingRequest authenticated with the session sId.
     * Wave uses HTTP Basic auth with an empty username and the sId as password:
     *   Authorization: Basic base64(":{sId}")
     */
    public function request(): PendingRequest
    {
        $pending = $this->publicRequest();

        if ($sId = $this->session->sId()) {
            $pending = $pending->withBasicAuth('', $sId);
        }

        return $pending;
    }

    /**
     * Execute a GraphQL mutation/query and return the parsed response.
     * Automatically runs through handle() for error mapping.
     */
    public function graphql(string $query, array $variables = [], bool $authenticated = true): array
    {
        // Post directly to the full api_url to avoid Guzzle's base URI
        // path-stripping behaviour when the URL has no trailing slash.
        $pending  = $authenticated ? $this->request() : $this->publicRequest();
        $response = $pending->post($this->config['api_url'], [
            'query'     => $query,
            'variables' => $variables,
        ]);

        $this->handle($response);

        $body = $response->json();

        if (isset($body['errors'])) {
            $message = $body['errors'][0]['message'] ?? 'Unknown GraphQL error';
            throw new WaveRequestException("Wave GraphQL error: {$message}", $response);
        }

        return $body['data'] ?? [];
    }

    /**
     * Central response handler: maps HTTP status codes to typed exceptions.
     */
    public function handle(Response $response): Response
    {
        if ($response->successful()) {
            return $response;
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw AuthenticationException::sessionExpired();
        }

        if ($response->status() === 429) {
            throw RateLimitException::fromResponse($response);
        }

        throw WaveRequestException::fromResponse($response);
    }

    private function defaultHeaders(): array
    {
        return [
            'Accept'               => '*/*',
            'Accept-Language'      => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
            'Content-Type'         => 'application/json',
            'Origin'               => $this->config['base_url'],
            'Referer'              => $this->config['base_url'] . '/',
            'Sec-Fetch-Dest'       => 'empty',
            'Sec-Fetch-Mode'       => 'cors',
            'Sec-Fetch-Site'       => 'same-site',
            'User-Agent'           => $this->config['http']['user_agent'],
            'X-Cloud-Trace-Context' => $this->generateTraceContext(),
        ];
    }

    private function generateTraceContext(): string
    {
        $traceId  = bin2hex(random_bytes(16));
        $spanId   = random_int(100_000_000, 9_999_999_999);

        return "{$traceId}/{$spanId};o=1";
    }
}
