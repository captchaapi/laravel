# Changelog

All notable changes to `captchaapi/laravel` are documented here.

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html)
and the format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [2.1.0] - 2026-05-11

### Added

- **`captchaapi.enabled` config key** as a master kill-switch for the
  package. When set to `false` (via `CAPTCHAAPI_ENABLED=false` in `.env`),
  `ValidCaptcha::validate()` passes silently and
  `<x-captchaapi::widget />` renders nothing. Lets you keep the
  validation rule, the Livewire trait, and the Blade markup in place
  across environments that don't have a live site key (local dev, CI,
  staging) without conditional render boilerplate or stubbed config.

  Defaults to `true`, so existing installs are unaffected. The new
  accessor `Captchaapi::enabled()` exposes the resolved value for
  consumer code that wants to gate its own logic on the same flag.

  Distinct from `FakeCaptchaapi::enable()`: fake mode is a per-test
  bypass that throws outside the testing environment; the new
  `enabled` flag is a permanent runtime switch safe to set in any
  environment.

## [2.0.1] - 2026-05-10

### Added

- **`<x-captchaapi::error />` Blade component** for rendering the
  CAPTCHA validation error (expired attestation, replay attempt, bad
  signature). Defaults to `:for="captcha_attestation"` — matching the
  field name used by `WithCaptcha` and `<x-captchaapi::livewire-form>`
  — and renders a `<p role="alert">`. Both `:for` and the wrapping tag
  (`:as`) are overridable, and extra attributes (e.g. `class`) merge
  through. Internally a thin wrapper around Laravel's `@error`
  directive; the README documents the manual `@error` form too for
  projects that prefer to keep markup in their own templates.

## [1.0.2] - 2026-05-04

### Fixed

- **Per-request memoization in `ValidCaptcha`** so the rule is safe to
  call multiple times with the same attestation within a single HTTP
  request. The canonical case is Laravel Fortify: its
  `Fortify::authenticateUsing` callback fires twice per login attempt
  (once via `RedirectIfTwoFactorAuthenticatable::validateCredentials`,
  once via `AttemptToAuthenticate::handle`), so a captcha rule wired
  into that callback would on its second invocation hit the jti cache
  set by its first invocation and reject as a replay. After this fix,
  the second within-request call short-circuits via the memoization
  flag and returns success without re-claiming the cache key. Replay
  protection across requests still works exactly as before.

  Recommended deployment: bump to 1.0.2, then re-enable
  `CAPTCHAAPI_REPLAY_PROTECTION=true` in any project that had to
  disable it as a workaround.

### Documentation

- README license badge switched from
  `img.shields.io/packagist/l/...` (depends on Packagist API + shields.io
  cache, returned "not found" until both refreshed after registration)
  to a static MIT badge. License is constant; no reason to fetch it
  dynamically.

## [1.0.1] - 2026-05-04

### Changed

- **Dropped Livewire 3 from supported matrix.** Package code is unchanged
  — the `WithCaptcha` trait and `<x-captchaapi::livewire-form>` Blade
  component use surface (`#[Validate]` attribute, `wire:model`,
  `$wire.set` / `$wire.{method}`) that is identical between Livewire 3
  and 4, so production users on Livewire 3 will likely still work.
  However, the test infrastructure (Testbench × Livewire 3 ×
  Laravel 11) trips on a realtime-facade autoloading timing issue
  inside Livewire 3's `SupportFileUploads::provide()` boot path, which
  cannot be reasonably worked around at the package level. CI now
  tests Livewire 4 only; `composer.json` `require-dev` and `suggest`
  drop the `^3.0` constraint.

### Documentation

- New "Status element styling" section in README documenting the three
  customization paths for the widget's visual status feedback:
  default colors out-of-the-box, per-state CSS override, or wholesale
  opt-out via `data-captcha-no-color` attribute. Reflects the
  underlying captcha.js change (now served from captchaapi.eu) which
  removed status element auto-injection in favour of strict opt-in,
  and switched default colors from inline `style.color` to a low-
  specificity injected stylesheet so customer CSS overrides cleanly
  without `!important`.

  Package code is unchanged — this is documentation catching up to
  upstream behaviour customers see when they install today.

## [1.0.0] - 2026-05-03

Initial release.

### Added

- `Captchaapi\Laravel\Rules\ValidCaptcha` — local HMAC verification of
  captchaapi.eu attestations. No HTTP round-trip on form submit.
- `'captcha'` validation alias for use as a Laravel string rule.
- Multi-secret support via the comma-separated `CAPTCHAAPI_SECRET_KEYS` env
  variable for zero-downtime secret rotation.
- Optional replay protection via cache-backed jti tracking
  (`replay_protection`, default on).
- `<x-captchaapi::widget />` Blade component — renders the client-side
  widget script with all configuration knobs filled in from
  `config/captchaapi.php`.
- `<x-captchaapi::livewire-form>` Blade component — drop-in form wrapper
  that uses the widget's `event` mode and dispatches to a Livewire action.
- `Captchaapi\Laravel\Concerns\WithCaptcha` Livewire trait — provides the
  `$captcha_attestation` property and validation rule wiring.
- `Captchaapi\Laravel\Facades\Captchaapi` facade with `fake()` /
  `unfake()` / `isFake()` for test mode bypass.
- `Captchaapi\Laravel\Testing\FakeCaptchaapi` test helper.
- 10-language translation file for the validation failure message
  (`en` + `cs` shipped, more locales follow consumer demand).
