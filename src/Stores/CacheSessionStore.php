<?php

namespace Alal\WaveClient\Stores;

use Alal\WaveClient\Contracts\SessionStore;
use Alal\WaveClient\Http\WaveSession;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class CacheSessionStore implements SessionStore
{
    public function __construct(
        private readonly CacheRepository $cache,
        private readonly string $key,
        private readonly int $ttl,
    ) {}

    public function get(): ?WaveSession
    {
        $data = $this->cache->get($this->key);

        if (!is_array($data)) {
            return null;
        }

        return WaveSession::fromArray($data);
    }

    public function put(WaveSession $session): void
    {
        $this->cache->put($this->key, $session->serialize(), $this->ttl);
    }

    public function forget(): void
    {
        $this->cache->forget($this->key);
    }
}
