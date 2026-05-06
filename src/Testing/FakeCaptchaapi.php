<?php

declare(strict_types=1);

namespace Captchaapi\Laravel\Testing;

use Captchaapi\Laravel\Captchaapi as CaptchaapiManager;

/** Test-only helpers: thin wrappers around Captchaapi::fake()/unfake(). */
final class FakeCaptchaapi
{
    public static function enable(): void
    {
        app(CaptchaapiManager::class)->fake();
    }

    public static function disable(): void
    {
        app(CaptchaapiManager::class)->unfake();
    }
}
