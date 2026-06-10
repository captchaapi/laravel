<?php

declare(strict_types=1);

namespace Captchaapi\Laravel;

use RuntimeException;

/** Singleton behind the Captchaapi facade: typed config accessors plus the fake() toggle. */
final class Captchaapi
{
    /** Form field the widget injects and the rule validates. One name across widget, Blade, trait, rule. */
    public const RESPONSE_FIELD = 'captchaapi_response';

    private bool $fake = false;

    /**
     * Restricted to the testing env: in Octane/Swoole/RoadRunner the singleton
     * survives across requests, so a stray call elsewhere would disable
     * verification permanently.
     *
     * @throws RuntimeException when called outside the testing environment.
     */
    public function fake(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException(
                'Captchaapi::fake() may only be called in the testing environment.'
            );
        }

        $this->fake = true;
    }

    public function unfake(): void
    {
        $this->fake = false;
    }

    public function isFake(): bool
    {
        return $this->fake;
    }

    public function enabled(): bool
    {
        return config()->boolean('captchaapi.enabled');
    }

    public function siteKey(): ?string
    {
        $value = config('captchaapi.site_key');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function baseUrl(): ?string
    {
        $value = config('captchaapi.base_url');
        if (! is_string($value) || $value === '') {
            return null;
        }

        $trimmed = rtrim($value, '/');

        return $trimmed === '' ? null : $trimmed;
    }

    public function secretKey(): ?string
    {
        $value = config('captchaapi.secret_key');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function verifyUrl(): string
    {
        return ($this->baseUrl() ?? 'https://captchaapi.eu').'/api/v1/captcha/verify';
    }

    public function verifyTimeout(): int
    {
        $value = (int) config('captchaapi.timeout', 5);

        return $value > 0 ? $value : 5;
    }

    public function failOpen(): bool
    {
        return config()->boolean('captchaapi.fail_open', true);
    }

    public function locale(): ?string
    {
        $value = config('captchaapi.locale');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function preload(): string
    {
        $value = config('captchaapi.preload', 'lazy');

        if ($value === 'eager') {
            return 'eager';
        }

        if ($value !== 'lazy' && $this->debug()) {
            logger()->warning('Captchaapi: invalid preload value, falling back to "lazy".', [
                'configured' => $value,
            ]);
        }

        return 'lazy';
    }

    public function debug(): bool
    {
        return (bool) config('captchaapi.debug', false);
    }
}
