<?php

namespace Alal\WaveClient\Contracts;

use Alal\WaveClient\Http\WaveSession;

interface SessionStore
{
    public function get(): ?WaveSession;

    public function put(WaveSession $session): void;

    public function forget(): void;
}
