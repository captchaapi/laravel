<?php

declare(strict_types=1);

use Captchaapi\Laravel\Facades\Captchaapi;
use Captchaapi\Laravel\Rules\ValidCaptcha;
use Captchaapi\Laravel\Testing\FakeCaptchaapi;

beforeEach(function (): void {
    Captchaapi::unfake();
});

afterEach(function (): void {
    app()['env'] = 'testing';
});

it('starts unfaked', function (): void {
    expect(Captchaapi::isFake())->toBeFalse();
});

it('FakeCaptchaapi::enable() flips fake on', function (): void {
    FakeCaptchaapi::enable();

    expect(Captchaapi::isFake())->toBeTrue();
});

it('FakeCaptchaapi::disable() flips fake off', function (): void {
    FakeCaptchaapi::enable();
    FakeCaptchaapi::disable();

    expect(Captchaapi::isFake())->toBeFalse();
});

it('a faked rule accepts any value', function (): void {
    FakeCaptchaapi::enable();

    $errors = [];
    (new ValidCaptcha)->validate('x', 'literal garbage value', function ($msg) use (&$errors): void {
        $errors[] = $msg;
    });

    expect($errors)->toBeEmpty();
});

it('Captchaapi::fake() throws outside the testing environment', function (): void {
    app()['env'] = 'production';

    expect(fn () => Captchaapi::fake())
        ->toThrow(RuntimeException::class, 'testing environment');
});
