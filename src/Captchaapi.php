<?php

declare(strict_types=1);

namespace Captchaapi\Laravel;

/**
 * Singleton-backed runtime state for the package.
 *
 * Exposed via the Captchaapi facade. Two responsibilities:
 *
 *   1. Configuration accessors (siteKey, baseUrl, locale, …) — typed wrappers
 *      around config('captchaapi.*') so callers don't deal with mixed return
 *      types from the framework helper.
 *
 *   2. Test mode toggle — when isFake() is true, ValidCaptcha short-circuits
 *      to "always pass" so feature tests don't have to mint real attestations.
 */
final class Captchaapi
{
    private bool $fake = false;

    public function fake(): void
    {
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

    public function siteKey(): ?string
    {
        $value = config('captchaapi.site_key');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function baseUrl(): ?string
    {
        $value = config('captchaapi.base_url');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function locale(): ?string
    {
        $value = config('captchaapi.locale');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function preload(): string
    {
        $value = config('captchaapi.preload', 'lazy');

        return $value === 'eager' ? 'eager' : 'lazy';
    }

    public function debug(): bool
    {
        return (bool) config('captchaapi.debug', false);
    }
}
