<?php

declare(strict_types=1);

use Captchaapi\Laravel\Concerns\WithCaptcha;
use Captchaapi\Laravel\Facades\Captchaapi;
use Captchaapi\Laravel\Rules\ValidCaptcha;
use Captchaapi\Laravel\Tests\Helpers\Attestation;

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

    $captchaRule = collect($rules['captcha_attestation'])->first(fn ($r): bool => $r instanceof ValidCaptcha);
    expect($captchaRule)->toBeInstanceOf(ValidCaptcha::class);
});

it('the trait property survives assignment of a real attestation string', function (): void {
    $component = new class
    {
        use WithCaptcha;
    };

    $component->captcha_attestation = Attestation::mint();

    expect($component->captcha_attestation)
        ->toBeString()
        ->toContain('.');
});

it('validateWithCaptcha() merges the captcha rule into caller-supplied rules', function (): void {
    $component = new class
    {
        use WithCaptcha;

        /** @var array<string, mixed>|null */
        public ?array $capturedRules = null;

        /**
         * @param  array<string, mixed>  $rules
         * @return array<string, mixed>
         */
        public function validate(array $rules = [], array $messages = [], array $attributes = []): array
        {
            $this->capturedRules = $rules;

            return [];
        }

        /**
         * @return array<string, mixed>
         */
        public function callValidateWithCaptcha(): array
        {
            return $this->validateWithCaptcha(['email' => 'required|email']);
        }
    };

    $component->callValidateWithCaptcha();

    expect($component->capturedRules)->toHaveKeys(['email', 'captcha_attestation']);
    expect($component->capturedRules['email'])->toBe('required|email');

    $captchaRule = collect($component->capturedRules['captcha_attestation'])
        ->first(fn ($r): bool => $r instanceof ValidCaptcha);
    expect($captchaRule)->toBeInstanceOf(ValidCaptcha::class);
});

it('the trait does not declare a #[Validate] attribute on captcha_attestation', function (): void {
    $reflection = new ReflectionProperty(
        new class
        {
            use WithCaptcha;
        },
        'captcha_attestation',
    );

    expect($reflection->getAttributes())->toBeEmpty();
});
