{{--
    <x-captchaapi::livewire-form action="register">
        ...form fields...
    </x-captchaapi::livewire-form>

    Drop-in <form> wrapper for Livewire components using the WithCaptcha trait.

    What it does:
      - Sets data-captcha + data-captcha-mode="event" so the widget dispatches
        a captchaapi:attested CustomEvent instead of calling form.submit()
        (which would bypass Livewire's AJAX submit pipeline).
      - Includes a hidden <input type="hidden" name="captcha_attestation"> that
        the widget writes to (defence-in-depth — the Livewire property is the
        primary transport).
      - Alpine listener catches captchaapi:attested, syncs the attestation into
        the Livewire component's $captcha_attestation property, then invokes
        the action method named in :action.

    The :action prop is REQUIRED. It is the Livewire method that will be called
    after the attestation is in place — equivalent to wire:submit on a normal
    Livewire form.

    Any extra HTML attributes (class, id, etc.) are forwarded to the <form>.
--}}
@props([
    'action',
])
@php
    $listener = sprintf(
        '$wire.captcha_attestation = $event.detail.attestation; $wire.%s()',
        addslashes($action),
    );
@endphp
<form
    {{ $attributes->merge([
        'data-captcha' => true,
        'data-captcha-mode' => 'event',
    ]) }}
    x-data
    x-on:captchaapi:attested="{{ $listener }}"
>
    <input type="hidden" name="captcha_attestation" wire:model="captcha_attestation">
    {{ $slot }}
</form>
