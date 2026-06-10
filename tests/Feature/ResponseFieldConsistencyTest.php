<?php

declare(strict_types=1);

use Captchaapi\Laravel\Captchaapi;
use Captchaapi\Laravel\Concerns\WithCaptcha;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

// captchaapi_response is one string spread across the widget (other repo), the
// Blade hidden input, the trait property, and the rule key. A typo in any one
// silently breaks the whole submit → verify flow, so this pins them together.

it('uses the same response field name across the trait, the rule, and the Blade form', function (): void {
    $field = Captchaapi::RESPONSE_FIELD;

    $component = new class
    {
        use WithCaptcha;

        /** @return array<string, mixed> */
        public function rules(): array
        {
            return $this->rulesForCaptcha();
        }
    };

    // Trait property name.
    expect(property_exists($component, $field))->toBeTrue();

    // Rule key.
    expect($component->rules())->toHaveKey($field);

    // Blade hidden input.
    $rendered = (string) view()->file(__DIR__.'/../fixtures/livewire-form.blade.php')->render();
    expect($rendered)->toContain('name="'.$field.'"');
});

it('wires the Blade form to the widget event and detail key the widget actually emits', function (): void {
    $rendered = (string) view()->file(__DIR__.'/../fixtures/livewire-form.blade.php')->render();

    // captcha.js dispatches `captchaapi:solved` with `detail.response`.
    expect($rendered)
        ->toContain('x-on:captchaapi:solved=')
        ->toContain('$event.detail.response')
        ->toContain('$wire.'.Captchaapi::RESPONSE_FIELD.' = $event.detail.response');
});

it('defaults the error component to the same field name', function (): void {
    $tmp = tempnam(sys_get_temp_dir(), 'captchaapi-err-').'.blade.php';
    file_put_contents($tmp, '<x-captchaapi::error />');

    try {
        $bag = new ViewErrorBag;
        $bag->put('default', new MessageBag([Captchaapi::RESPONSE_FIELD => 'nope']));
        view()->share('errors', $bag);

        $rendered = (string) view()->file($tmp)->render();

        expect($rendered)->toContain('>nope<');
    } finally {
        @unlink($tmp);
    }
});
