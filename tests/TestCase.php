<?php

namespace Alal\WaveClient\Tests;

use Alal\WaveClient\WaveClientServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [WaveClientServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Wave' => \Alal\WaveClient\Facades\Wave::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('wave-client.base_url', 'https://business.wave.com');
        $app['config']->set('wave-client.session.store', 'array');
        $app['config']->set('cache.default', 'array');
    }
}
