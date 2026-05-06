<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Site key
    |--------------------------------------------------------------------------
    |
    | Public site key from https://captchaapi.eu/dashboard. Safe to expose
    | in the browser.
    |
    */

    'site_key' => env('CAPTCHAAPI_SITE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Secret keys
    |--------------------------------------------------------------------------
    |
    | Comma-separated HMAC secrets. The list form enables zero-downtime
    | rotation: any matching key in the list accepts the attestation.
    |
    */

    'secret_keys' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CAPTCHAAPI_SECRET_KEYS', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Override the API origin. Use only when self-hosting / proxying.
    |
    */

    'base_url' => env('CAPTCHAAPI_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Locale
    |--------------------------------------------------------------------------
    |
    | Force the widget UI language. The package ships server-side validation
    | translations for `en` and `cs`; the widget itself supports more. When
    | null, falls back to <html lang>, then English.
    |
    */

    'locale' => env('CAPTCHAAPI_LOCALE'),

    /*
    |--------------------------------------------------------------------------
    | Preload mode
    |--------------------------------------------------------------------------
    |
    | 'lazy' (default) waits for first form interaction; 'eager' fires the
    | challenge on page load (snappier submit, costs one challenge per view).
    |
    */

    'preload' => env('CAPTCHAAPI_PRELOAD', 'lazy'),

    /*
    |--------------------------------------------------------------------------
    | Debug mode
    |--------------------------------------------------------------------------
    |
    | Logs widget timing breakdown to the browser console.
    |
    */

    'debug' => (bool) env('CAPTCHAAPI_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Submit mode
    |--------------------------------------------------------------------------
    |
    | 'submit' (widget default) calls native form.submit() after attesting;
    | 'event' dispatches a captchaapi:attested CustomEvent instead — needed
    | for Livewire / Inertia / htmx / fetch flows. Per-form
    | data-captcha-mode attributes always win.
    |
    */

    'mode' => env('CAPTCHAAPI_MODE'),

    /*
    |--------------------------------------------------------------------------
    | Replay protection
    |--------------------------------------------------------------------------
    |
    | Cache each accepted attestation's jti for the remainder of its TTL
    | and reject duplicates. Uses the application's default cache; disable
    | only if your cache is unreliable.
    |
    */

    'replay_protection' => (bool) env('CAPTCHAAPI_REPLAY_PROTECTION', true),

    /*
    |--------------------------------------------------------------------------
    | Cache key prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for replay-protection cache keys. Change only on collision.
    |
    */

    'cache_prefix' => 'captchaapi:jti:',

    /*
    |--------------------------------------------------------------------------
    | Clock-skew leeway
    |--------------------------------------------------------------------------
    |
    | Seconds the attestation's `iat` (issued-at) may sit in the future
    | before being rejected. Tolerates small clock drift.
    |
    */

    'clock_skew_leeway' => (int) env('CAPTCHAAPI_CLOCK_SKEW_LEEWAY', 60),

];
