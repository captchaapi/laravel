<?php

declare(strict_types=1);

namespace Captchaapi\Laravel\Testing;

use Captchaapi\Laravel\Captchaapi as CaptchaapiManager;

/**
 * Convenience helpers for feature tests that submit forms with a captcha
 * field but don't want to mint real attestations.
 *
 * Usage in a Pest test:
 *
 *   beforeEach(function () {
 *       FakeCaptchaapi::enable();
 *   });
 *
 *   afterEach(function () {
 *       FakeCaptchaapi::disable();
 *   });
 *
 * Or simply call Captchaapi::fake() / Captchaapi::unfake() directly — this
 * helper exists so test files don't have to import the facade alongside
 * their own assertions.
 */
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
