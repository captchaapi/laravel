{{--
    <form> wrapper for Livewire components using the WithCaptcha trait.
    :action is required and must be a valid PHP identifier — it's interpolated
    into the inline x-on / wire:submit handler.

    When captchaapi.enabled is false the wrapper falls back to a plain
    wire:submit="{$action}" form so submissions still work without the
    captcha widget.
--}}
@props([
    'action',
])
@php
    if (! is_string($action) || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $action) !== 1) {
        throw new \InvalidArgumentException(
            'x-captchaapi::livewire-form :action must be a valid PHP identifier (Livewire method name); got '.var_export($action, true)
        );
    }
@endphp
@if(\Captchaapi\Laravel\Facades\Captchaapi::enabled())
    @php
        $listener = '$wire.captcha_attestation = $event.detail.attestation; $wire.'.$action.'()';
    @endphp
    <form
        {{ $attributes->merge([
            'data-captcha' => true,
            'data-captcha-mode' => 'event',
        ]) }}
        x-data
        x-on:captchaapi:attested="{{ $listener }}"
    >
        <input type="hidden" name="captcha_attestation">
        {{ $slot }}
    </form>
@else
    <form {{ $attributes->merge(['wire:submit' => $action]) }}>
        {{ $slot }}
    </form>
@endif
