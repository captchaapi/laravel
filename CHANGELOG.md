# Changelog

All notable changes to `captchaapi/laravel` are documented here.

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html)
and the format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

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
