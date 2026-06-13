<?php

namespace Alal\WaveClient\Facades;

use Alal\WaveClient\Actions\Balance;
use Alal\WaveClient\Actions\PaymentRequest;
use Alal\WaveClient\Actions\Payout;
use Alal\WaveClient\Actions\Transactions;
use Alal\WaveClient\Auth\Authenticator;
use Alal\WaveClient\Contracts\WaveClient;
use Alal\WaveClient\Testing\WaveFake;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Authenticator   auth()
 * @method static Balance         balance()
 * @method static Payout          payout()
 * @method static Transactions    transactions()
 * @method static PaymentRequest  paymentRequest()
 *
 * @see \Alal\WaveClient\WaveManager
 */
class Wave extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WaveClient::class;
    }

    public static function fake(?WaveFake $fake = null): WaveFake
    {
        $fake ??= new WaveFake();

        static::swap($fake);

        return $fake;
    }
}
