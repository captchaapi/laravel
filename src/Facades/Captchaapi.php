<?php

declare(strict_types=1);

namespace Captchaapi\Laravel\Facades;

use Captchaapi\Laravel\Captchaapi as CaptchaapiManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null siteKey()
 * @method static string|null baseUrl()
 * @method static string|null locale()
 * @method static string preload()
 * @method static bool debug()
 * @method static bool enabled()
 *
 * @see CaptchaapiManager
 */
final class Captchaapi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CaptchaapiManager::class;
    }

    /**
     * Override of Facade::fake() (Laravel's mocking infrastructure) so the call
     * reaches our manager singleton instead of the inherited stub.
     */
    public static function fake(): void
    {
        self::getFacadeRoot()->fake();
    }

    public static function unfake(): void
    {
        self::getFacadeRoot()->unfake();
    }

    public static function isFake(): bool
    {
        return self::getFacadeRoot()->isFake();
    }
}
