{{--
    Loads the captchaapi.eu widget on the current page. Place once per layout.
    All props default to config/captchaapi.php values; per-form data-captcha-mode
    still overrides the global :mode set here.

    Renders nothing when config('captchaapi.enabled') is false — single switch
    to drop the widget out of local / CI / staging without conditionals in
    every layout.
--}}
@props([
    'siteKey' => null,
    'baseUrl' => null,
    'locale'  => null,
    'preload' => null,
    'debug'   => null,
    'mode'    => null,
])
@if(\Captchaapi\Laravel\Facades\Captchaapi::enabled())
@php
    $resolvedSiteKey = $siteKey ?? \Captchaapi\Laravel\Facades\Captchaapi::siteKey();
    $resolvedBaseUrl = $baseUrl ?? \Captchaapi\Laravel\Facades\Captchaapi::baseUrl();
    $resolvedLocale  = $locale  ?? \Captchaapi\Laravel\Facades\Captchaapi::locale();
    $resolvedPreload = $preload ?? \Captchaapi\Laravel\Facades\Captchaapi::preload();
    $resolvedDebug   = $debug   ?? \Captchaapi\Laravel\Facades\Captchaapi::debug();
    $resolvedMode    = $mode    ?? config('captchaapi.mode');
    $widgetSrc       = ($resolvedBaseUrl ? rtrim($resolvedBaseUrl, '/') : 'https://captchaapi.eu').'/captcha.js';
@endphp
<script>
    @if($resolvedSiteKey)
        window.CAPTCHA_SITE_KEY = @json($resolvedSiteKey);
    @endif
    @if($resolvedBaseUrl)
        window.CAPTCHA_BASE_URL = @json(rtrim($resolvedBaseUrl, '/').'/api/v1');
    @endif
    @if($resolvedLocale)
        window.CAPTCHA_LOCALE = @json($resolvedLocale);
    @endif
    @if($resolvedPreload && $resolvedPreload !== 'lazy')
        window.CAPTCHA_PRELOAD = @json($resolvedPreload);
    @endif
    @if($resolvedDebug)
        window.CAPTCHA_DEBUG = true;
    @endif
    @if($resolvedMode === 'event' || $resolvedMode === 'submit')
        window.CAPTCHA_MODE = @json($resolvedMode);
    @endif
</script>
<script src="{{ $widgetSrc }}" defer></script>
@endif
