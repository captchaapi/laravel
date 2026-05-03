<?php

declare(strict_types=1);

use Captchaapi\Laravel\Concerns\WithCaptcha;
use Captchaapi\Laravel\Facades\Captchaapi;
use Captchaapi\Laravel\Rules\ValidCaptcha;

beforeEach(function (): void {
    Captchaapi::unfake();
});

it('the trait exposes a string captcha_attestation public property defaulting to empty', function (): void {
    $component = new class
    {
        use WithCaptcha;
    };

    expect($component->captcha_attestation)->toBe('');
});

it('the trait declares the rule via a protected method that returns ValidCaptcha', function (): void {
    $component = new class
    {
        use WithCaptcha;

        public function rules(): array
        {
            return $this->rulesForCaptcha();
        }
    };

    $rules = $component->rules();

    expect($rules)->toHaveKey('captcha_attestation');
    expect($rules['captcha_attestation'])->toContain('required', 'string');

    $captchaRule = collect($rules['captcha_attestation'])->first(fn ($r) => $r instanceof ValidCaptcha);
    expect($captchaRule)->toBeInstanceOf(ValidCaptcha::class);
});

it('the trait property survives assignment of a real attestation string', function (): void {
    $component = new class
    {
        use WithCaptcha;
    };

    $component->captcha_attestation = mintAttestation();

    expect($component->captcha_attestation)
        ->toBeString()
        ->toContain('.');
});
