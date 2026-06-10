# captchaapi/laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/captchaapi/laravel.svg?style=flat-square)](https://packagist.org/packages/captchaapi/laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/captchaapi/laravel.svg?style=flat-square)](https://packagist.org/packages/captchaapi/laravel)
[![Tests](https://img.shields.io/github/actions/workflow/status/captchaapi/laravel/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/captchaapi/laravel/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/packagist/dependency-v/captchaapi/laravel/php?style=flat-square)](https://packagist.org/packages/captchaapi/laravel)
[![Laravel Compatibility](https://badge.laravel.cloud/badge/captchaapi/laravel?style=flat)](https://packagist.org/packages/captchaapi/laravel)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

Official Laravel SDK for [captchaapi.eu](https://captchaapi.eu/?utm_source=github&utm_medium=referral&utm_campaign=laravel-package) — EU-hosted,
GDPR-compliant proof-of-work CAPTCHA. Drop-in Blade component, validation
rule, and Livewire trait. No cookies, no tracking, no Google.

## Why captchaapi.eu

- **EU-hosted** (Hetzner Nuremberg) — GDPR-compliant by default, no data
  ever leaves the EU.
- **Proof-of-work** — invisible to legitimate visitors, no friction
  puzzles to solve.
- **Server-side verification** — your backend confirms each response with
  captchaapi.eu over a single call, secured by your secret key. The same
  model every major CAPTCHA uses, so the secret never reaches the browser.
- **Livewire-native** — first-class trait + Blade wrapper instead of just
  plain HTML form support.

## Requirements

- PHP 8.2+
- Laravel 12 or 13
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
CAPTCHAAPI_SITE_KEY=pk_live_...
CAPTCHAAPI_SECRET_KEY=sk_live_...
```

The site key is public and goes in the browser; the secret stays on your
server. Get both from the [project dashboard](https://captchaapi.eu/dashboard).

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
    'captchaapi_response' => ['required', 'string', new ValidCaptcha],
]);
```

Or via the string alias:

```php
$request->validate([
    'email'               => ['required', 'email'],
    'captchaapi_response' => ['required', 'captcha'],
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

        // proceed — captchaapi_response has been verified
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
`captchaapi_response` input, and dispatches to your Livewire `register`
method once the response arrives.

### Showing the validation error

When `ValidCaptcha` rejects a response (invalid, expired, or already
used), Laravel attaches the message to the `captchaapi_response` field.
Render it with the included helper:

```blade
<x-captchaapi::error />
```

Defaults to `:for="captchaapi_response"` and renders a `<p role="alert">`.
Override the field name, the wrapping tag, and pass through any
attributes:

```blade
<x-captchaapi::error for="captchaapi_response" as="span" class="text-red-600" />
```

The component is a thin wrapper around Laravel's `@error` directive — if
you prefer to keep markup in your own templates, write it yourself:

```blade
@error('captchaapi_response')
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

## Disabling the package

Flip `CAPTCHAAPI_ENABLED=false` in `.env` to disable both the validation
rule and the widget without removing any wiring. The `ValidCaptcha` rule
passes silently and `<x-captchaapi::widget />` renders nothing, so you
can keep the trait, the rule, and the Blade markup in place across
local, CI, and staging environments where no live site key is set.

Defaults to `true`, so existing installs keep working unchanged. This is
a permanent kill-switch, not a per-test bypass — for that, use
`FakeCaptchaapi::enable()` (see [Testing](#testing)).

## Configuration reference

| Config key   | ENV variable                | Default | Purpose                                                                                                |
| ------------ | --------------------------- | ------- | ------------------------------------------------------------------------------------------------------ |
| `enabled`    | `CAPTCHAAPI_ENABLED`        | `true`  | Master kill-switch. When false, the rule passes silently and the widget renders nothing.               |
| `site_key`   | `CAPTCHAAPI_SITE_KEY`       | `null`  | Public site key from the dashboard. Required for widget rendering.                                      |
| `secret_key` | `CAPTCHAAPI_SECRET_KEY`         | `null`  | Project secret, sent as a Bearer token on the verify call. Server-side only.                            |
| `base_url`   | `CAPTCHAAPI_BASE_URL`       | `null`  | Override the API origin for the widget and the verify call. Defaults to `https://captchaapi.eu`.        |
| `timeout`    | `CAPTCHAAPI_VERIFY_TIMEOUT` | `5`     | Seconds to wait for the verify call before treating the server as unreachable.                          |
| `fail_open`  | `CAPTCHAAPI_FAIL_OPEN`      | `true`  | When the server is unreachable or returns a 5xx: `true` lets the submission through, `false` rejects it with a try-again message. |
| `locale`     | `CAPTCHAAPI_LOCALE`         | `null`  | Force widget language (`en`, `de`, `cs`, …). Falls back to `<html lang>` then `en`.                     |
| `preload`    | `CAPTCHAAPI_PRELOAD`        | `lazy`  | `lazy` waits for first form interaction; `eager` fires the challenge on page load.                      |
| `debug`      | `CAPTCHAAPI_DEBUG`          | `false` | Log timing breakdown in the browser console.                                                            |
| `mode`       | `CAPTCHAAPI_MODE`           | `null`  | `submit` (native form POST) or `event` (CustomEvent for Livewire/SPA).                                  |

## Fail policy

The verify call can fail to reach a verdict — the server is unreachable or
returns a 5xx. `fail_open` decides what happens, and it defaults to **true**:
the submission goes through. A CAPTCHA guards a public form, so your own
outage blocking every submission is worse than the rare bot slipping past
during it, and an attacker can't reach this path anyway — verification is
server-to-server, off the browser.

Set it to **false** for sensitive actions (login, payment) where a missed bot
costs more than a blocked visitor:

```dotenv
CAPTCHAAPI_FAIL_OPEN=false
```

The visitor is then asked to try again, never told they failed the CAPTCHA.
There is no automatic retry on either setting: the response is single-use, and
a second verify call would spend a token the visitor already solved.

## Secret key rotation

Rotate from the dashboard — the package needs no list of keys. While a
rotation is pending, the server accepts **both** the old and the new secret on
every challenge issued, so you deploy the new key without a hard cutover:

1. In the dashboard, click **Rotate secret key** — issues a new key while the
   current one keeps working.
2. Update `CAPTCHAAPI_SECRET_KEY` in your `.env` and deploy. From here both keys
   verify, so the timing of your deploy doesn't matter.
3. In the dashboard, activate the new key, then retire the old one.

The only thing tied to the old key is a challenge a visitor was already
solving when you clicked **Rotate** — it lives at most the token lifetime
(~2 minutes), so any brief overlap clears itself.

For a suspected compromise, use **Revoke immediately** to drop the old key in
one step. This skips the overlap, so in-flight solutions fail until the new
secret is deployed.

## Testing

In feature tests, enable fake mode so `ValidCaptcha` accepts any input
without a real response or a call to the server:

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
