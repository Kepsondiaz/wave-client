<?php

namespace Alal\WaveClient\Actions;

use Alal\WaveClient\Data\PayoutData;
use Alal\WaveClient\Http\WaveConnector;

class Payout
{
    // STUB: replace with the real paths from your captured requests.
    private const SEND_PATH   = '/api/payout';
    private const GET_PATH    = '/api/payout/{id}';
    private const SEARCH_PATH = '/api/payout/search';

    public function __construct(
        private readonly WaveConnector $connector,
    ) {}

    /**
     * Send money to a mobile number.
     *
     * STUB: adjust the POST body keys to match your captured request.
     *
     * @param  string  $mobile  Recipient phone in E.164 format (e.g. +221XXXXXXXX)
     * @param  string  $amount  Amount as a string (e.g. '5000')
     * @param  array{
     *   name?: string,
     *   reference?: string,
     *   reason?: string,
     * } $options
     */
    public function send(string $mobile, string $amount, array $options = []): PayoutData
    {
        $response = $this->connector->request()->post(self::SEND_PATH, [
            'mobile'           => $mobile,
            'amount'           => $amount,
            'currency'         => 'XOF',
            'name'             => $options['name'] ?? null,
            'client_reference' => $options['reference'] ?? null,
            'payment_reason'   => $options['reason'] ?? null,
        ]);

        $this->connector->handle($response);

        return PayoutData::from($response->json() ?? []);
    }

    /**
     * Retrieve a specific payout by ID.
     */
    public function find(string $id): PayoutData
    {
        $path = str_replace('{id}', $id, self::GET_PATH);
        $response = $this->connector->request()->get($path);
        $this->connector->handle($response);

        return PayoutData::from($response->json() ?? []);
    }

    /**
     * Search payouts with optional filters.
     *
     * STUB: adjust query param names to match your captured request.
     *
     * @param  array{
     *   from?: string,
     *   to?: string,
     *   status?: string,
     *   mobile?: string,
     *   limit?: int,
     * } $filters
     * @return PayoutData[]
     */
    public function search(array $filters = []): array
    {
        $response = $this->connector->request()->get(self::SEARCH_PATH, $filters);
        $this->connector->handle($response);

        $items = $response->json('results') ?? $response->json('data') ?? $response->json() ?? [];

        return array_map(fn(array $item) => PayoutData::from($item), $items);
    }
}
