{{--
    <x-captchaapi::widget />

    Loads the captchaapi.eu client-side widget on the current page. Place once
    in your layout (typically in <head> or before </body>). Reads every config
    knob from config/captchaapi.php so consumers don't repeat themselves.

    Optional props let you override per-page:
      :site-key   force a different site key (e.g. multi-tenant apps)
      :base-url   override the API origin (self-hosted / proxied)
      :locale     override the widget UI language
      :preload    'lazy' (default) | 'eager'
      :debug      true | false
      :mode       'submit' (default) | 'event' — global default for every form

    The data-captcha-mode attribute on individual <form> tags still takes
    precedence over the global :mode setting here.
--}}
@props([
    'siteKey' => null,
    'baseUrl' => null,
    'locale'  => null,
    'preload' => null,
    'debug'   => null,
    'mode'    => null,
])
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
