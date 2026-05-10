{{--
    Renders the captcha validation error using Laravel's @error directive.
    Defaults match the captcha_attestation field name used by WithCaptcha
    and the livewire-form wrapper. Renders nothing when no error is present.
--}}
@props([
    'for' => 'captcha_attestation',
    'as'  => 'p',
])
@error($for)
    <{{ $as }} {{ $attributes->merge(['role' => 'alert']) }}>{{ $message }}</{{ $as }}>
@enderror
