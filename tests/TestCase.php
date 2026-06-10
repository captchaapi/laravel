<?php

declare(strict_types=1);

namespace Captchaapi\Laravel\Tests;

use Captchaapi\Laravel\CaptchaapiServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            CaptchaapiServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('captchaapi.enabled', true);
        $app['config']->set('captchaapi.site_key', 'test_site_key');
        $app['config']->set('captchaapi.secret_key', 'test_secret_key');
        $app['config']->set('captchaapi.base_url', null);
        $app['config']->set('captchaapi.fail_open', true);
    }
}
