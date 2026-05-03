<?php

declare(strict_types=1);

namespace Captchaapi\Laravel\Concerns;

use Captchaapi\Laravel\Rules\ValidCaptcha;
use Livewire\Attributes\Validate;

/**
 * Drop into a Livewire component to wire up captchaapi.eu attestation
 * validation with zero ceremony. The widget (in event mode) writes the
 * attestation into the public property via the bundled Blade hidden input,
 * and the #[Validate] attribute runs the rule on every submit.
 *
 * Usage:
 *
 *   class RegisterForm extends Component
 *   {
 *       use WithCaptcha;
 *
 *       public string $email = '';
 *
 *       public function register(): void
 *       {
 *           $this->validate(['email' => 'required|email']);
 *           // captcha_attestation is validated by the trait's #[Validate]
 *           // attribute on every $this->validate() / $this->validateOnly() call.
 *       }
 *   }
 *
 * In the component view, wrap the form with <x-captchaapi::livewire-form> —
 * it places the hidden input bound to $captcha_attestation and listens for
 * the captchaapi:attested CustomEvent to call the wire:submit method.
 */
trait WithCaptcha
{
    #[Validate(['required', 'string'], onUpdate: false)]
    public string $captcha_attestation = '';

    /**
     * Custom validation rules — merged with the property-level #[Validate].
     * Implements the actual ValidCaptcha rule here (separate from the
     * #[Validate] attribute) so the rule's failure message stays unified
     * with classic FormRequest usage.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rulesForCaptcha(): array
    {
        return [
            'captcha_attestation' => ['required', 'string', new ValidCaptcha],
        ];
    }
}
