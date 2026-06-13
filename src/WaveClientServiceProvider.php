<?php

namespace Alal\WaveClient;

use Alal\WaveClient\Console\LoginCommand;
use Alal\WaveClient\Contracts\SessionStore;
use Alal\WaveClient\Contracts\WaveClient;
use Alal\WaveClient\Stores\CacheSessionStore;
use Illuminate\Support\ServiceProvider;

class WaveClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/wave-client.php', 'wave-client');

        $this->app->singleton(SessionStore::class, function ($app) {
            $config = $app['config']['wave-client.session'];

            return new CacheSessionStore(
                cache: $app['cache']->store($config['store']),
                key:   $config['key'],
                ttl:   $config['ttl'],
            );
        });

        $this->app->singleton(WaveManager::class, function ($app) {
            return new WaveManager(
                config:       $app['config']['wave-client'],
                sessionStore: $app->make(SessionStore::class),
            );
        });

        $this->app->alias(WaveManager::class, WaveClient::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/wave-client.php' => config_path('wave-client.php'),
            ], 'wave-client-config');

            $this->commands([LoginCommand::class]);
        }
    }
}
