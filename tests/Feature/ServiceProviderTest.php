<?php

declare(strict_types=1);

use Captchaapi\Laravel\Captchaapi;
use Captchaapi\Laravel\CaptchaapiServiceProvider;
use Captchaapi\Laravel\Tests\Helpers\Attestation;
use Illuminate\Support\Facades\Validator;

it('registers the Captchaapi singleton', function (): void {
    expect(app(Captchaapi::class))->toBeInstanceOf(Captchaapi::class);
    expect(app(Captchaapi::class))->toBe(app(Captchaapi::class));
});

it('merges the package config under the captchaapi key', function (): void {
    expect(config('captchaapi.site_key'))->toBe('test_site_key');
    expect(config('captchaapi.replay_protection'))->toBeTrue();
});

it('publishes the config tag', function (): void {
    $tags = collect(CaptchaapiServiceProvider::pathsToPublish(CaptchaapiServiceProvider::class, 'captchaapi-config'));

    expect($tags)->not->toBeEmpty();
});

it('registers the captcha string validation alias and fails invalid input', function (): void {
    $validator = Validator::make(
        ['captcha_attestation' => 'obvious garbage'],
        ['captcha_attestation' => 'required|captcha'],
    );

    expect($validator->fails())->toBeTrue();
});

it('registers the captcha string validation alias and passes a real attestation', function (): void {
    $validator = Validator::make(
        ['captcha_attestation' => Attestation::mint()],
        ['captcha_attestation' => 'required|captcha'],
    );

    expect($validator->passes())->toBeTrue();
});

it('loads the package translations under the captchaapi namespace', function (): void {
    expect(trans('captchaapi::validation.failed'))
        ->toBe('CAPTCHA verification failed. Please refresh the page and try again.');
});
