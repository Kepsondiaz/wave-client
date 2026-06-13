<?php

namespace Alal\WaveClient\Console;

use Alal\WaveClient\Contracts\SessionStore;
use Alal\WaveClient\Exceptions\AuthenticationException;
use Alal\WaveClient\WaveManager;
use Illuminate\Console\Command;

class KeepaliveCommand extends Command
{
    protected $signature = 'wave:keepalive';

    protected $description = 'Ping the Wave API to keep the cached session alive. Schedule this hourly.';

    public function handle(WaveManager $wave, SessionStore $store): int
    {
        $session = $store->get();

        if (!$session || !$session->isAuthenticated()) {
            $this->warn('No active Wave session found. Run php artisan wave:login first.');
            return self::FAILURE;
        }

        try {
            // Re-persist with a refreshed TTL so the cache doesn't evict it.
            $store->put($session);

            // Make a lightweight GraphQL introspection call to keep Wave's
            // server-side session warm. Replace with any real query once wired.
            $wave->connector()->graphql(
                '{ __typename }',
                [],
                authenticated: true,
            );

            $this->info('Wave session is alive and TTL refreshed.');
            return self::SUCCESS;
        } catch (AuthenticationException $e) {
            $this->error('Wave session has expired. Re-run php artisan wave:login.');
            $store->forget();
            return self::FAILURE;
        }
    }
}
