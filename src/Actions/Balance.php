<?php

namespace Alal\WaveClient\Actions;

use Alal\WaveClient\Data\BalanceData;
use Alal\WaveClient\Http\WaveConnector;

class Balance
{
    // STUB: replace with the real path from your captured request.
    private const PATH = '/api/balance';

    public function __construct(
        private readonly WaveConnector $connector,
    ) {}

    public function get(): BalanceData
    {
        $response = $this->connector->request()->get(self::PATH);
        $this->connector->handle($response);

        return BalanceData::from($response->json() ?? []);
    }
}
