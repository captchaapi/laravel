<?php

declare(strict_types=1);

use Captchaapi\Laravel\Facades\Captchaapi;
use Illuminate\Support\Facades\Log;

it('preload() returns "eager" when configured', function (): void {
    config(['captchaapi.preload' => 'eager']);

    expect(Captchaapi::preload())->toBe('eager');
});

it('preload() falls back to "lazy" for unknown values', function (): void {
    config(['captchaapi.preload' => 'bogus']);

    expect(Captchaapi::preload())->toBe('lazy');
});

it('preload() logs a debug-mode warning for unknown values', function (): void {
    config(['captchaapi.preload' => 'bogus', 'captchaapi.debug' => true]);
    Log::spy();

    expect(Captchaapi::preload())->toBe('lazy');

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => str_contains($message, 'invalid preload value')
            && ($context['configured'] ?? null) === 'bogus');
});

it('baseUrl() strips a trailing slash', function (): void {
    config(['captchaapi.base_url' => 'https://proxy.example.com/']);

    expect(Captchaapi::baseUrl())->toBe('https://proxy.example.com');
});

it('baseUrl() returns null when only slashes are configured', function (): void {
    config(['captchaapi.base_url' => '///']);

    expect(Captchaapi::baseUrl())->toBeNull();
});

it('enabled() reflects config(captchaapi.enabled)', function (): void {
    config(['captchaapi.enabled' => true]);
    expect(Captchaapi::enabled())->toBeTrue();

    config(['captchaapi.enabled' => false]);
    expect(Captchaapi::enabled())->toBeFalse();
});
