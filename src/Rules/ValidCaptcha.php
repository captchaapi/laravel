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
 * Payload fields: sk (site_key), iat, exp, jti (replay), ol (over_limit, informational).
 *
 * Verification is local — no HTTP round-trip. Any key in
 * config('captchaapi.secret_keys') accepts the attestation, enabling
 * zero-downtime key rotation.
 */
final class ValidCaptcha implements ValidationRule
{
    private const MAX_JTI_LENGTH = 128;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Captchaapi::isFake()) {
            return;
        }

        if (! is_string($value) || ! str_contains($value, '.')) {
            $fail($this->failureMessage());

            return;
        }

        // Frameworks like Fortify invoke the validator twice per request; memoize
        // successful results so the jti claim doesn't reject the second call.
        $memoKey = $this->memoKey($value);
        if ($this->isMemoized($memoKey)) {
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

        $this->memoize($memoKey);
    }

    /** Constant-time scan: always runs to completion so timing doesn't leak which key matched. */
    private function signatureMatchesAnySecret(string $payloadB64, string $signature): bool
    {
        /** @var list<string> $secrets */
        $secrets = (array) config('captchaapi.secret_keys', []);

        $matched = false;
        foreach ($secrets as $secret) {
            $expected = hash_hmac('sha256', $payloadB64, (string) $secret, binary: true);
            // OR after the call so hash_equals always executes.
            $matched = hash_equals($expected, $signature) || $matched;
        }

        return $matched;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadIsFresh(array $payload): bool
    {
        $now = time();
        $exp = $payload['exp'] ?? null;
        $iat = $payload['iat'] ?? null;

        if (! is_int($exp) || $exp < $now) {
            return false;
        }

        if (! is_int($iat)) {
            return false;
        }

        $leeway = max(0, (int) config('captchaapi.clock_skew_leeway', 60));

        return $iat <= $now + $leeway;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadMatchesSiteKey(array $payload): bool
    {
        $siteKey = (string) ($payload['sk'] ?? '');
        $configured = (string) config('captchaapi.site_key', '');

        return $siteKey !== '' && $configured !== '' && $siteKey === $configured;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function claimAndCacheJti(array $payload): bool
    {
        $jti = $payload['jti'] ?? null;
        if (! is_string($jti) || $jti === '') {
            // Legacy attestation without jti — replay protection doesn't apply.
            return true;
        }

        if (strlen($jti) > self::MAX_JTI_LENGTH) {
            return false;
        }

        $key = ((string) config('captchaapi.cache_prefix', 'captchaapi:jti:')).$jti;
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

    private function memoKey(string $attestation): string
    {
        // xxh64 — fast, short, non-cryptographic; the attestation is already authenticated.
        return '_captchaapi_validated_'.hash('xxh64', $attestation);
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
}
