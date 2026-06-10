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

it('secret() returns the configured secret or null when blank', function (): void {
    config(['captchaapi.secret' => 'sk_live_abc']);
    expect(Captchaapi::secret())->toBe('sk_live_abc');

    config(['captchaapi.secret' => '']);
    expect(Captchaapi::secret())->toBeNull();

    config(['captchaapi.secret' => null]);
    expect(Captchaapi::secret())->toBeNull();
});

it('verifyUrl() appends the verify path to the default origin', function (): void {
    config(['captchaapi.base_url' => null]);

    expect(Captchaapi::verifyUrl())->toBe('https://captchaapi.eu/api/v1/captcha/verify');
});

it('verifyUrl() honours a base_url override', function (): void {
    config(['captchaapi.base_url' => 'https://proxy.example.com/']);

    expect(Captchaapi::verifyUrl())->toBe('https://proxy.example.com/api/v1/captcha/verify');
});

it('verifyTimeout() returns the configured seconds, falling back to 5 for non-positive values', function (): void {
    config(['captchaapi.timeout' => 12]);
    expect(Captchaapi::verifyTimeout())->toBe(12);

    config(['captchaapi.timeout' => 0]);
    expect(Captchaapi::verifyTimeout())->toBe(5);
});

it('failOpen() defaults to true and reflects config', function (): void {
    config(['captchaapi.fail_open' => true]);
    expect(Captchaapi::failOpen())->toBeTrue();

    config(['captchaapi.fail_open' => false]);
    expect(Captchaapi::failOpen())->toBeFalse();
});
