{{--
    Renders the captcha validation error using Laravel's @error directive.
    Defaults match the captchaapi_response field name used by WithCaptcha
    and the livewire-form wrapper. Renders nothing when no error is present.
--}}
@props([
    'for' => 'captchaapi_response',
    'as'  => 'p',
])
@error($for)
    <{{ $as }} {{ $attributes->merge(['role' => 'alert']) }}>{{ $message }}</{{ $as }}>
@enderror
