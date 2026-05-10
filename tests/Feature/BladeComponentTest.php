<?php

declare(strict_types=1);

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

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
    expect($rendered)->not->toContain('wire:model');
    expect($rendered)->toContain('$wire.captcha_attestation = $event.detail.attestation');
    expect($rendered)->toContain('$wire.register()');
    expect($rendered)->toContain('SLOT_CONTENT_MARKER');
});

it('<x-captchaapi::livewire-form> rejects invalid action names', function (): void {
    $tmp = tempnam(sys_get_temp_dir(), 'captchaapi-form-').'.blade.php';
    file_put_contents($tmp, '<x-captchaapi::livewire-form action="not a valid identifier"></x-captchaapi::livewire-form>');

    try {
        $rendered = false;
        try {
            view()->file($tmp)->render();
            $rendered = true;
        } catch (Throwable $e) {
            // Blade wraps render-time exceptions in ViewException; walk the chain.
            $cause = $e;
            while ($cause instanceof Throwable && ! $cause instanceof InvalidArgumentException) {
                $cause = $cause->getPrevious();
            }

            expect($cause)->toBeInstanceOf(InvalidArgumentException::class);
            expect($cause->getMessage())->toContain('must be a valid PHP identifier');
        }
        expect($rendered)->toBeFalse();
    } finally {
        @unlink($tmp);
    }
});

it('<x-captchaapi::livewire-form> forwards extra HTML attributes to the form', function (): void {
    $rendered = (string) view()->file(__DIR__.'/../fixtures/livewire-form-with-attrs.blade.php')->render();

    expect($rendered)->toContain('class="space-y-4"');
    expect($rendered)->toContain('id="signup-form"');
});

it('<x-captchaapi::error /> renders nothing when no error is present', function (): void {
    view()->share('errors', new ViewErrorBag);

    $rendered = trim((string) view()->file(__DIR__.'/../fixtures/error-default.blade.php')->render());

    expect($rendered)->toBe('');
});

it('<x-captchaapi::error /> renders the captcha_attestation error in a <p role="alert">', function (): void {
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag(['captcha_attestation' => 'CAPTCHA verification failed.']));
    view()->share('errors', $bag);

    $rendered = (string) view()->file(__DIR__.'/../fixtures/error-default.blade.php')->render();

    expect($rendered)->toContain('<p role="alert">CAPTCHA verification failed.</p>');
});

it('<x-captchaapi::error /> respects :for, :as, and merges extra attributes', function (): void {
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag(['my_field' => 'oops']));
    view()->share('errors', $bag);

    $rendered = (string) view()->file(__DIR__.'/../fixtures/error-overridden.blade.php')->render();

    expect($rendered)
        ->toContain('<span')
        ->toContain('role="alert"')
        ->toContain('class="text-red-600"')
        ->toContain('>oops</span>');
});
