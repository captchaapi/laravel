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
     * Enable test mode — ValidCaptcha will accept any input.
     *
     * Explicit override of the inherited Facade::fake() (which is Laravel's
     * facade-mocking infrastructure, unrelated to our domain bypass). Without
     * this override, Captchaapi::fake() would silently call the wrong method
     * and our test-mode toggle would never reach the manager singleton.
     */
    public static function fake(): void
    {
        self::getFacadeRoot()->fake();
    }

    /**
     * Disable test mode — restore real attestation validation.
     */
    public static function unfake(): void
    {
        self::getFacadeRoot()->unfake();
    }

    /**
     * Whether test mode is currently active.
     */
    public static function isFake(): bool
    {
        return self::getFacadeRoot()->isFake();
    }
}
