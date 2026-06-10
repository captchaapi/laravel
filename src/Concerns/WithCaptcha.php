<?php

declare(strict_types=1);

namespace Captchaapi\Laravel\Concerns;

use Captchaapi\Laravel\Captchaapi as CaptchaapiManager;
use Captchaapi\Laravel\Facades\Captchaapi;
use Captchaapi\Laravel\Rules\ValidCaptcha;

/** Livewire trait: adds $captchaapi_response and the validateWithCaptcha() helper. */
trait WithCaptcha
{
    public string $captchaapi_response = '';

    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     * @return array<string, mixed>
     */
    protected function validateWithCaptcha(array $rules = [], array $messages = [], array $attributes = []): array
    {
        /** @phpstan-ignore-next-line method exists on Livewire\Component at runtime */
        return $this->validate(array_merge($rules, $this->rulesForCaptcha()), $messages, $attributes);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rulesForCaptcha(): array
    {
        if (! Captchaapi::enabled()) {
            return [];
        }

        return [
            CaptchaapiManager::RESPONSE_FIELD => ['required', 'string', new ValidCaptcha],
        ];
    }
}
