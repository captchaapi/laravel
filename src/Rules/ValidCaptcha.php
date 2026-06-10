<?php

declare(strict_types=1);

namespace Captchaapi\Laravel\Rules;

use Captchaapi\Laravel\Facades\Captchaapi;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Verifies a captchaapi.eu response server-to-server: POST the widget's
 * `captchaapi_response` value to /verify with the project secret as a Bearer
 * token and accept the submission only when the server returns success.
 *
 * The response is single-use — the server consumes it on the first verify — so
 * there is exactly one attempt, never a retry. When the server can't be reached
 * or returns a 5xx, the fail policy (config `fail_open`) decides; a definitive
 * "not valid" answer always rejects.
 */
final class ValidCaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Captchaapi::enabled() || Captchaapi::isFake()) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail($this->rejectionMessage());

            return;
        }

        // Frameworks like Fortify run the validator twice in one request. The
        // response is single-use server-side, so the second verify would hit an
        // already-consumed token and reject a visitor who passed the first time.
        // Memoize a success per request and short-circuit the repeat call.
        $memoKey = $this->memoKey($value);

        if ($this->isMemoized($memoKey)) {
            return;
        }

        $secret = Captchaapi::secret();

        if ($secret === null) {
            $fail($this->rejectionMessage());

            return;
        }

        try {
            $response = Http::asJson()
                ->withToken($secret)
                ->timeout(Captchaapi::verifyTimeout())
                ->post(Captchaapi::verifyUrl(), ['response' => $value]);
        } catch (ConnectionException) {
            $this->whenUnavailable($fail);

            return;
        }

        // A 5xx is our outage, not the visitor's fault — same fail policy as an
        // unreachable server. No retry: a second call would hit an
        // already-consumed token and reject a visitor who solved the captcha.
        if ($response->serverError()) {
            $this->whenUnavailable($fail);

            return;
        }

        if ($response->json('success') === true) {
            $this->memoize($memoKey);

            return;
        }

        $fail($this->rejectionMessage());
    }

    private function memoKey(string $value): string
    {
        // Non-cryptographic — the value is opaque and the key only spans one request.
        return '_captchaapi_verified_'.hash('xxh64', $value);
    }

    private function isMemoized(string $memoKey): bool
    {
        if (! app()->bound('request')) {
            return false;
        }

        return (bool) request()->attributes->get($memoKey, false);
    }

    private function memoize(string $memoKey): void
    {
        if (! app()->bound('request')) {
            return;
        }

        request()->attributes->set($memoKey, true);
    }

    private function whenUnavailable(Closure $fail): void
    {
        if (Captchaapi::failOpen()) {
            return;
        }

        $fail($this->unavailableMessage());
    }

    private function rejectionMessage(): string
    {
        return (string) trans('captchaapi::validation.failed');
    }

    private function unavailableMessage(): string
    {
        return (string) trans('captchaapi::validation.unavailable');
    }
}
