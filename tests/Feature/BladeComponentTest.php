<?php

declare(strict_types=1);

it('<x-captchaapi::widget /> renders a script tag with the configured site key', function (): void {
    $rendered = (string) view()->file(__DIR__.'/../fixtures/widget-default.blade.php')->render();

    expect($rendered)->toContain('window.CAPTCHA_SITE_KEY = "test_site_key"');
    expect($rendered)->toContain('<script src="https://captchaapi.eu/captcha.js" defer></script>');
});

it('<x-captchaapi::widget /> includes locale when configured', function (): void {
    config(['captchaapi.locale' => 'cs']);

    $rendered = (string) view()->file(__DIR__.'/../fixtures/widget-default.blade.php')->render();

    expect($rendered)->toContain('window.CAPTCHA_LOCALE = "cs"');
});

it('<x-captchaapi::widget /> emits CAPTCHA_PRELOAD only when not lazy', function (): void {
    config(['captchaapi.preload' => 'lazy']);
    expect((string) view()->file(__DIR__.'/../fixtures/widget-default.blade.php')->render())
        ->not->toContain('CAPTCHA_PRELOAD');

    config(['captchaapi.preload' => 'eager']);
    expect((string) view()->file(__DIR__.'/../fixtures/widget-default.blade.php')->render())
        ->toContain('window.CAPTCHA_PRELOAD = "eager"');
});

it('<x-captchaapi::widget /> emits CAPTCHA_DEBUG only when truthy', function (): void {
    config(['captchaapi.debug' => false]);
    expect((string) view()->file(__DIR__.'/../fixtures/widget-default.blade.php')->render())
        ->not->toContain('CAPTCHA_DEBUG');

    config(['captchaapi.debug' => true]);
    expect((string) view()->file(__DIR__.'/../fixtures/widget-default.blade.php')->render())
        ->toContain('window.CAPTCHA_DEBUG = true');
});

it('<x-captchaapi::widget /> emits CAPTCHA_MODE only for valid values', function (): void {
    config(['captchaapi.mode' => null]);
    expect((string) view()->file(__DIR__.'/../fixtures/widget-default.blade.php')->render())
        ->not->toContain('CAPTCHA_MODE');

    config(['captchaapi.mode' => 'event']);
    expect((string) view()->file(__DIR__.'/../fixtures/widget-default.blade.php')->render())
        ->toContain('window.CAPTCHA_MODE = "event"');

    config(['captchaapi.mode' => 'bogus']);
    expect((string) view()->file(__DIR__.'/../fixtures/widget-default.blade.php')->render())
        ->not->toContain('CAPTCHA_MODE');
});

it('<x-captchaapi::widget /> rewrites the script src when base_url is set', function (): void {
    config(['captchaapi.base_url' => 'https://proxy.example.com']);

    $rendered = (string) view()->file(__DIR__.'/../fixtures/widget-default.blade.php')->render();

    expect($rendered)->toContain('<script src="https://proxy.example.com/captcha.js" defer></script>');
    expect($rendered)->toContain('window.CAPTCHA_BASE_URL = "https:\/\/proxy.example.com\/api\/v1"');
});

it('<x-captchaapi::widget /> accepts inline prop overrides', function (): void {
    $rendered = (string) view()->file(__DIR__.'/../fixtures/widget-overridden.blade.php')->render();

    expect($rendered)->toContain('window.CAPTCHA_SITE_KEY = "override_key"');
    expect($rendered)->toContain('window.CAPTCHA_LOCALE = "de"');
    expect($rendered)->toContain('window.CAPTCHA_DEBUG = true');
});

it('<x-captchaapi::livewire-form> wraps content with data-captcha + event mode', function (): void {
    $rendered = (string) view()->file(__DIR__.'/../fixtures/livewire-form.blade.php')->render();

    expect($rendered)->toContain('data-captcha');
    expect($rendered)->toContain('data-captcha-mode="event"');
    expect($rendered)->toContain('<input type="hidden" name="captcha_attestation"');
    expect($rendered)->toContain('wire:model="captcha_attestation"');
    expect($rendered)->toContain('$wire.register()');
    expect($rendered)->toContain('SLOT_CONTENT_MARKER');
});

it('<x-captchaapi::livewire-form> forwards extra HTML attributes to the form', function (): void {
    $rendered = (string) view()->file(__DIR__.'/../fixtures/livewire-form-with-attrs.blade.php')->render();

    expect($rendered)->toContain('class="space-y-4"');
    expect($rendered)->toContain('id="signup-form"');
});
