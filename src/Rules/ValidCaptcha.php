<?php

declare(strict_types=1);

namespace Captchaapi\Laravel\Rules;

use Captchaapi\Laravel\Facades\Captchaapi;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;

/**
 * Validates a captchaapi.eu attestation produced by the browser widget.
 *
 * Attestation format: "{base64url(payload_json)}.{base64url(hmac_sha256(payload_b64, secret_key))}"
 * Payload fields:
 *   sk  — site_key (must match the configured site key)
 *   iat — issued at (unix seconds)
 *   exp — expires at (unix seconds, must be in the future)
 *   jti — UUID for optional single-use replay protection
 *   ol  — over_limit flag (informational; does not affect validity)
 *
 * The check is purely local — no HTTP round-trip to captchaapi.eu. Multi-secret
 * support enables zero-downtime key rotation: any matching key in
 * config('captchaapi.secret_keys') accepts the attestation.
 *
 * Replay protection is opt-out via config('captchaapi.replay_protection'). When
 * enabled, each accepted jti is cached for the remainder of its lifetime and
 * subsequent submissions of the same attestation are rejected.
 */
final class ValidCaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Fake mode (test helper) bypasses every check — see Captchaapi::fake().
        if (Captchaapi::isFake()) {
            return;
        }

        if (! is_string($value) || ! str_contains($value, '.')) {
            $fail($this->failureMessage());

            return;
        }

        [$payloadB64, $sigB64] = explode('.', $value, 2);

        $signature = self::base64UrlDecode($sigB64);
        if ($signature === null) {
            $fail($this->failureMessage());

            return;
        }

        if (! $this->signatureMatchesAnySecret($payloadB64, $signature)) {
            $fail($this->failureMessage());

            return;
        }

        $rawPayload = self::base64UrlDecode($payloadB64);
        if ($rawPayload === null) {
            $fail($this->failureMessage());

            return;
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($rawPayload, associative: true);
        if (! is_array($payload)) {
            $fail($this->failureMessage());

            return;
        }

        if (! $this->payloadIsFresh($payload) || ! $this->payloadMatchesSiteKey($payload)) {
            $fail($this->failureMessage());

            return;
        }

        if (config('captchaapi.replay_protection') && ! $this->claimAndCacheJti($payload)) {
            $fail($this->failureMessage());

            return;
        }
    }

    /**
     * Constant-time scan of every configured secret key. Returns true on the
     * first match. The loop runs to completion in the worst case — list of
     * keys is small (1–2 during a rotation window) so the timing leakage of
     * loop length is negligible compared to the per-key hash_equals.
     */
    private function signatureMatchesAnySecret(string $payloadB64, string $signature): bool
    {
        /** @var list<string> $secrets */
        $secrets = (array) config('captchaapi.secret_keys', []);

        foreach ($secrets as $secret) {
            $expected = hash_hmac('sha256', $payloadB64, (string) $secret, binary: true);
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadIsFresh(array $payload): bool
    {
        $exp = $payload['exp'] ?? null;

        return is_int($exp) && $exp >= time();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadMatchesSiteKey(array $payload): bool
    {
        $siteKey = (string) ($payload['sk'] ?? '');
        $configured = (string) config('captchaapi.site_key', '');

        return $siteKey !== '' && $configured !== '' && hash_equals($configured, $siteKey);
    }

    /**
     * Atomically reserve the jti so a second submission of the same attestation
     * is rejected. Cache TTL matches the attestation's remaining lifetime so we
     * never hold entries longer than they're useful.
     *
     * @param  array<string, mixed>  $payload
     */
    private function claimAndCacheJti(array $payload): bool
    {
        $jti = $payload['jti'] ?? null;
        if (! is_string($jti) || $jti === '') {
            // No jti = legacy attestation. Treat as accepted (signature + exp + sk
            // already passed); replay protection silently doesn't apply.
            return true;
        }

        $key = (config('captchaapi.cache_prefix', 'captchaapi:jti:')).$jti;
        $ttl = max(1, (int) $payload['exp'] - time());

        // Cache::add returns false if the key already existed → replay attempt.
        return Cache::add($key, true, $ttl);
    }

    private function base64UrlDecode(string $value): ?string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), strict: true);

        return $decoded === false ? null : $decoded;
    }

    private function failureMessage(): string
    {
        return (string) trans('captchaapi::validation.failed');
    }
}
