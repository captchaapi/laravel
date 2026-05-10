# captchaapi/laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/captchaapi/laravel.svg?style=flat-square)](https://packagist.org/packages/captchaapi/laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/captchaapi/laravel.svg?style=flat-square)](https://packagist.org/packages/captchaapi/laravel)
[![Tests](https://img.shields.io/github/actions/workflow/status/captchaapi/laravel/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/captchaapi/laravel/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

Official Laravel SDK for [captchaapi.eu](https://captchaapi.eu) — EU-hosted,
GDPR-compliant proof-of-work CAPTCHA. Drop-in Blade component, validation
rule, and Livewire trait. No cookies, no tracking, no Google.

## Why captchaapi.eu

- **EU-hosted** (Hetzner Frankfurt) — GDPR-compliant by default, no data
  ever leaves the EU.
- **Proof-of-work** — invisible to legitimate visitors, no friction
  puzzles to solve.
- **Local HMAC verification** — no server-to-server round-trip on every
  form submit; your backend verifies the signed attestation against your
  secret key in pure PHP.
- **Livewire-native** — first-class trait + Blade wrapper instead of just
  plain HTML form support.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- (Optional) Livewire 4 for the trait + livewire-form component

## Installation

```bash
composer require captchaapi/laravel
```

Publish the config file:

```bash
php artisan vendor:publish --tag=captchaapi-config
```

Set the credentials in `.env`:

```dotenv
CAPTCHAAPI_SITE_KEY=sk_pub_your_site_key
CAPTCHAAPI_SECRET_KEYS=your_current_secret
# During rotation:
# CAPTCHAAPI_SECRET_KEYS=your_current_secret,your_pending_secret
```

Get your keys from the [project dashboard](https://captchaapi.eu/dashboard).

## Usage

### Plain HTML form

In your layout's `<head>` (renders the widget script and pushes config to
`window.CAPTCHA_*`):

```blade
<x-captchaapi::widget />
```

In your form, add `data-captcha`:

```blade
<form action="/contact" method="POST" data-captcha>
    @csrf
    <input type="email" name="email" required>
    <button type="submit">Send</button>
</form>
```

In your validation:

```php
use Captchaapi\Laravel\Rules\ValidCaptcha;

$request->validate([
    'email'               => ['required', 'email'],
    'captcha_attestation' => ['required', 'string', new ValidCaptcha],
]);
```

Or via the string alias:

```php
$request->validate([
    'email'               => ['required', 'email'],
    'captcha_attestation' => ['required', 'captcha'],
]);
```

### Livewire

In your layout (same as above):

```blade
<x-captchaapi::widget />
```

In your Livewire component:

```php
use Captchaapi\Laravel\Concerns\WithCaptcha;
use Livewire\Component;

class RegisterForm extends Component
{
    use WithCaptcha;

    public string $email = '';

    public function register(): void
    {
        $this->validateWithCaptcha([
            'email' => 'required|email',
        ]);

        // proceed — captcha_attestation has been validated
    }
}
```

`validateWithCaptcha()` is sugar for `$this->validate(array_merge($rules, $this->rulesForCaptcha()))`.
If you need to compose `validateOnly()` flows yourself, `rulesForCaptcha()` is still public.

In the component view, use the Livewire-aware form wrapper:

```blade
<div>
    <x-captchaapi::livewire-form action="register" class="space-y-4">
        <input wire:model="email" type="email" required>
        <button type="submit">Register</button>
    </x-captchaapi::livewire-form>
</div>
```

The wrapper sets `data-captcha-mode="event"`, includes the hidden
attestation input, and dispatches to your Livewire `register` method
once the attestation arrives.

### Showing the validation error

When `ValidCaptcha` rejects an attestation (expired, replayed, malformed
signature), Laravel attaches the message to the `captcha_attestation`
field. Render it with the included helper:

```blade
<x-captchaapi::error />
```

Defaults to `:for="captcha_attestation"` and renders a `<p role="alert">`.
Override the field name, the wrapping tag, and pass through any
attributes:

```blade
<x-captchaapi::error for="captcha_attestation" as="span" class="text-red-600" />
```

The component is a thin wrapper around Laravel's `@error` directive — if
you prefer to keep markup in your own templates, write it yourself:

```blade
@error('captcha_attestation')
    <p role="alert">{{ $message }}</p>
@enderror
```

Both approaches work for plain HTML forms (after a redirect-with-errors)
and for Livewire (after `validateWithCaptcha()`).

## Status element styling

Place a `<div data-captcha-status></div>` anywhere inside (or near)
your form to show the widget's current state to your users. Six
states exist (`waiting`, `idle`, `solving`, `ready`, `error`,
`rate_limited`), each rendered as an icon + a localised message. The
widget sets `data-captcha-state="…"` on the element so you can target
each state with CSS.

The status element is **opt-in**. A form without a
`[data-captcha-status]` child runs silently — submission still works,
only the visible signal is absent. There is no auto-injection.

### Default colors

The widget injects a low-specificity stylesheet on first use that
applies sensible default colors per state:

| State          | Default color            |
| -------------- | ------------------------ |
| `waiting`      | `#6b7280` grey           |
| `idle`         | `#6b7280` grey           |
| `solving`      | `#6b7280` grey           |
| `ready`        | `#059669` emerald        |
| `error`        | `#dc2626` red            |
| `rate_limited` | `#d97706` amber          |

You don't have to write any CSS to get those colors. They appear
automatically on every `[data-captcha-status]` element the widget
finds.

### Override one state

Write a higher-specificity rule (two attribute selectors instead of
one). The customer rule wins regardless of cascade order — no
`!important` needed:

```css
[data-captcha-status][data-captcha-state="ready"] { color: #047857; }
[data-captcha-status][data-captcha-state="error"] { color: #b91c1c; }
```

### Take over completely (Tailwind / design system)

Add `data-captcha-no-color` to suppress the widget's default
stylesheet entirely. Your own classes / CSS apply cleanly:

```html
<div data-captcha-status data-captcha-no-color
     class="text-cyan-700 dark:text-cyan-300"></div>
```

Note that `waiting` and `ready` share the shield icon and would be
visually identical without color. If you opt out of the widget
defaults, supply per-state colors in your own CSS so the two stay
distinguishable.

## Configuration reference

| Config key                  | ENV variable                    | Default | Purpose                                                                                  |
| --------------------------- | ------------------------------- | ------- | ---------------------------------------------------------------------------------------- |
| `site_key`                  | `CAPTCHAAPI_SITE_KEY`           | `null`  | Public site key from the dashboard. Required for widget rendering.                       |
| `secret_keys`               | `CAPTCHAAPI_SECRET_KEYS`        | `[]`    | Comma-separated HMAC secrets. Multi-value enables zero-downtime rotation.                |
| `base_url`                  | `CAPTCHAAPI_BASE_URL`           | `null`  | Override the API origin. Use only when self-hosting / proxying.                          |
| `locale`                    | `CAPTCHAAPI_LOCALE`             | `null`  | Force widget language (`en`, `de`, `cs`, …). Falls back to `<html lang>` then `en`.      |
| `preload`                   | `CAPTCHAAPI_PRELOAD`            | `lazy`  | `lazy` waits for first form interaction; `eager` fires the challenge on page load.       |
| `debug`                     | `CAPTCHAAPI_DEBUG`              | `false` | Log timing breakdown in the browser console.                                             |
| `mode`                      | `CAPTCHAAPI_MODE`               | `null`  | `submit` (native form POST) or `event` (CustomEvent for Livewire/SPA).                   |
| `replay_protection`         | `CAPTCHAAPI_REPLAY_PROTECTION`  | `true`  | Cache each attestation `jti` and reject duplicates within its TTL window.                |
| `cache_prefix`              | —                               | `captchaapi:jti:` | Prefix for cached jtis. Change only on collision with another package.         |
| `clock_skew_leeway`         | `CAPTCHAAPI_CLOCK_SKEW_LEEWAY`  | `60`    | Seconds an attestation's `iat` may sit in the future before it is rejected.              |

## Secret key rotation

The package accepts any matching secret in `CAPTCHAAPI_SECRET_KEYS` (a
comma-separated list). Rotation has four steps:

1. In the dashboard, click **Rotate secret key** — generates a new key
   in the *pending* state. Your backend keeps signing with the current key.
2. Add the pending key alongside the current one in your `.env`:
   ```dotenv
   CAPTCHAAPI_SECRET_KEYS=current_secret,pending_secret
   ```
   Deploy.
3. In the dashboard, click **Activate pending key**. The backend now
   signs with the new key; your app accepts both during the handover.
4. Drop the old key on the next deploy.

For suspected-compromise scenarios, use the dashboard's **Revoke
immediately** — replaces the key in one step and skips the pending
phase. Briefly accepts no attestations until you deploy the new key.

## Replay protection

By default the validation rule caches each accepted attestation's `jti`
(unique identifier in the payload) for the remainder of its TTL. A
captured-in-transit attestation can therefore be submitted only once,
even within its 5-minute validity window.

This requires a working cache driver (the application's default cache
via `Cache::store()`). Disable in `config/captchaapi.php` if your cache
is unreliable or unavailable:

```php
'replay_protection' => false,
```

Use **Redis**, **Memcached**, or the **database** cache driver in
production. The `file` and `array` drivers don't have an atomic
`Cache::add()`, so concurrent submissions can race past the replay
check.

## Testing

In feature tests, enable fake mode so `ValidCaptcha` accepts any input
without requiring you to mint real attestations:

```php
use Captchaapi\Laravel\Testing\FakeCaptchaapi;

beforeEach(function () {
    FakeCaptchaapi::enable();
});

afterEach(function () {
    FakeCaptchaapi::disable();
});
```

The fake state is stored on the `Captchaapi` singleton — it does not
persist across requests in production code.

## Security

If you discover a security vulnerability, please **do not** open a
public GitHub issue. Use GitHub's [private vulnerability reporting](https://github.com/captchaapi/laravel/security/advisories/new)
so we can coordinate a fix before details become public.

## Contributing

Bug reports and feature requests welcome at
[github.com/captchaapi/laravel/issues](https://github.com/captchaapi/laravel/issues).

For development:

```bash
composer install
composer test     # Pest, parallel
composer lint     # Pint --test
composer stan     # PHPStan level 8
composer rector   # Rector --dry-run
```

## License

MIT — see [LICENSE](LICENSE).
