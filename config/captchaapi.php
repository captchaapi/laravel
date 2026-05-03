<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Site key
    |--------------------------------------------------------------------------
    |
    | Public identifier for your captchaapi.eu project. Safe to ship to the
    | browser — the widget reads this from window.CAPTCHA_SITE_KEY when it
    | calls /api/v1/captcha/challenge. Get it from your project dashboard
    | at https://captchaapi.eu/dashboard.
    |
    */

    'site_key' => env('CAPTCHAAPI_SITE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Secret keys
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of HMAC secrets your backend uses to verify
    | attestations. The list form enables zero-downtime rotation: deploy
    | the new key alongside the current one, activate the new one in the
    | dashboard, then drop the old one on the next deploy.
    |
    | Stored as ENV csv (e.g. "current_secret,pending_secret"), parsed into
    | a list here. Empty / whitespace entries are filtered out.
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
    | Origin the widget loads from. Override only when you self-host the API
    | or proxy it behind your own domain. The trailing /api/v1 path segment
    | is added by the widget itself.
    |
    */

    'base_url' => env('CAPTCHAAPI_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Locale
    |--------------------------------------------------------------------------
    |
    | Force a specific UI language for the widget status messages
    | ("Protection active", "Verifying form protection…", etc.). Supported:
    | en, de, fr, es, it, pl, nl, pt, cs, ro. When null, the widget falls
    | back to <html lang>, then to English.
    |
    */

    'locale' => env('CAPTCHAAPI_LOCALE'),

    /*
    |--------------------------------------------------------------------------
    | Preload mode
    |--------------------------------------------------------------------------
    |
    | When the widget fires the /challenge request. 'lazy' (default) waits
    | for first form interaction (pointerdown / keydown / touchstart / input).
    | 'eager' fires on DOMContentLoaded — snappier submit UX but consumes
    | one challenge per pageview regardless of whether the visitor submits.
    |
    | Opt into 'eager' only when your conversion funnel is thick enough to
    | justify the quota cost (e.g. a single-purpose form page where most
    | visitors do submit).
    |
    */

    'preload' => env('CAPTCHAAPI_PRELOAD', 'lazy'),

    /*
    |--------------------------------------------------------------------------
    | Debug mode
    |--------------------------------------------------------------------------
    |
    | When true, the widget logs end-to-end timing breakdown (challenge
    | round-trip, PoW solve duration + iteration count, verify round-trip,
    | total) to the browser console. Useful for verifying performance on
    | your own hardware before going live.
    |
    */

    'debug' => (bool) env('CAPTCHAAPI_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Submit mode
    |--------------------------------------------------------------------------
    |
    | Default submission strategy for every form on the page. 'submit' (the
    | widget's built-in default) calls native form.submit() after acquiring
    | an attestation — correct for plain HTML forms. 'event' instead has the
    | widget dispatch a captchaapi:attested CustomEvent and skip the native
    | submit, which is what Livewire / Inertia / htmx / fetch flows need.
    |
    | When null (default) the widget's own default ('submit') applies.
    | Per-form data-captcha-mode attributes always win over this setting.
    |
    */

    'mode' => env('CAPTCHAAPI_MODE'),

    /*
    |--------------------------------------------------------------------------
    | Replay protection
    |--------------------------------------------------------------------------
    |
    | When true (default), the validation rule caches each attestation's jti
    | (UUID) and rejects any duplicate within the attestation TTL window. An
    | attacker who captures a valid attestation in transit therefore cannot
    | replay it. Requires a working cache driver — uses the application's
    | default cache via Cache::store().
    |
    | Set to false only if your cache is unreliable or unavailable in your
    | deployment.
    |
    */

    'replay_protection' => (bool) env('CAPTCHAAPI_REPLAY_PROTECTION', true),

    /*
    |--------------------------------------------------------------------------
    | Cache key prefix
    |--------------------------------------------------------------------------
    |
    | Prefix used by replay protection when caching attestation jtis. Change
    | only if it collides with another package in your cache.
    |
    */

    'cache_prefix' => 'captchaapi:jti:',

];
