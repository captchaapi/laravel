<?php

declare(strict_types=1);

use Captchaapi\Laravel\Concerns\WithCaptcha;
use Captchaapi\Laravel\Facades\Captchaapi;
use Captchaapi\Laravel\Rules\ValidCaptcha;

beforeEach(function (): void {
    Captchaapi::unfake();
});

it('the trait exposes a string captchaapi_response public property defaulting to empty', function (): void {
    $component = new class
    {
        use WithCaptcha;
    };

    expect($component->captchaapi_response)->toBe('');
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

    expect($rules)->toHaveKey('captchaapi_response');
    expect($rules['captchaapi_response'])->toContain('required', 'string');

    $captchaRule = collect($rules['captchaapi_response'])->first(fn ($r): bool => $r instanceof ValidCaptcha);
    expect($captchaRule)->toBeInstanceOf(ValidCaptcha::class);
});

it('the trait property survives assignment of a real response string', function (): void {
    $component = new class
    {
        use WithCaptcha;
    };

    $component->captchaapi_response = 'token.solution';

    expect($component->captchaapi_response)
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

    expect($component->capturedRules)->toHaveKeys(['email', 'captchaapi_response']);
    expect($component->capturedRules['email'])->toBe('required|email');

    $captchaRule = collect($component->capturedRules['captchaapi_response'])
        ->first(fn ($r): bool => $r instanceof ValidCaptcha);
    expect($captchaRule)->toBeInstanceOf(ValidCaptcha::class);
});

it('the trait does not declare a #[Validate] attribute on captchaapi_response', function (): void {
    $reflection = new ReflectionProperty(
        new class
        {
            use WithCaptcha;
        },
        'captchaapi_response',
    );

    expect($reflection->getAttributes())->toBeEmpty();
});

it('rulesForCaptcha() returns an empty array when captchaapi.enabled is false', function (): void {
    config(['captchaapi.enabled' => false]);

    $component = new class
    {
        use WithCaptcha;

        /**
         * @return array<string, mixed>
         */
        public function rules(): array
        {
            return $this->rulesForCaptcha();
        }
    };

    expect($component->rules())->toBe([]);
});

it('validateWithCaptcha() does not inject captcha rules when captchaapi.enabled is false', function (): void {
    config(['captchaapi.enabled' => false]);

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

    expect($component->capturedRules)
        ->toBe(['email' => 'required|email'])
        ->not->toHaveKey('captchaapi_response');
});
