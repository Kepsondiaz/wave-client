<?php

namespace Alal\WaveClient\Contracts;

interface WaveClient
{
    public function auth(): mixed;
    public function balance(): mixed;
    public function payout(): mixed;
    public function transactions(): mixed;
    public function paymentRequest(): mixed;
}
