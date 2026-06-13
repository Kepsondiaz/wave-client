<?php

namespace Alal\WaveClient\Actions;

use Alal\WaveClient\Data\PaymentRequestData;
use Alal\WaveClient\Http\WaveConnector;

class PaymentRequest
{
    // STUB: replace with the real paths from your captured requests.
    private const CREATE_PATH = '/api/payment-request';
    private const GET_PATH    = '/api/payment-request/{id}';
    private const CANCEL_PATH = '/api/payment-request/{id}/cancel';

    public function __construct(
        private readonly WaveConnector $connector,
    ) {}

    /**
     * Create a payment request (collect link / QR).
     *
     * STUB: adjust the POST body keys to match your captured request.
     *
     * @param  array{
     *   amount: string,
     *   currency?: string,
     *   reference?: string,
     *   description?: string,
     *   mobile?: string,
     * } $params
     */
    public function create(array $params): PaymentRequestData
    {
        $response = $this->connector->request()->post(self::CREATE_PATH, [
            'amount'           => $params['amount'],
            'currency'         => $params['currency'] ?? 'XOF',
            'client_reference' => $params['reference'] ?? null,
            'description'      => $params['description'] ?? null,
            'mobile'           => $params['mobile'] ?? null,
        ]);

        $this->connector->handle($response);

        return PaymentRequestData::from($response->json() ?? []);
    }

    /**
     * Retrieve a payment request by ID.
     */
    public function find(string $id): PaymentRequestData
    {
        $path = str_replace('{id}', $id, self::GET_PATH);
        $response = $this->connector->request()->get($path);
        $this->connector->handle($response);

        return PaymentRequestData::from($response->json() ?? []);
    }

    /**
     * Cancel an open payment request.
     */
    public function cancel(string $id): PaymentRequestData
    {
        $path = str_replace('{id}', $id, self::CANCEL_PATH);
        $response = $this->connector->request()->post($path);
        $this->connector->handle($response);

        return PaymentRequestData::from($response->json() ?? []);
    }
}
