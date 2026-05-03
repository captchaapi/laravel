<?php

declare(strict_types=1);

use Captchaapi\Laravel\Facades\Captchaapi;
use Captchaapi\Laravel\Rules\ValidCaptcha;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Captchaapi::unfake();
    Cache::flush();
});

// ─── Happy path ───────────────────────────────────────────────────────────────

it('accepts a freshly minted attestation', function (): void {
    expect(runRule(mintAttestation()))->toBeNull();
});

it('accepts an attestation signed by a non-primary secret in the rotation list', function (): void {
    config(['captchaapi.secret_keys' => ['stale_primary', 'test_secret_key']]);

    expect(runRule(mintAttestation()))->toBeNull();
});

// ─── Format validation ────────────────────────────────────────────────────────

it('rejects a value that is not a string', function (): void {
    expect(runRule(['not', 'a', 'string']))->not->toBeNull();
});

it('rejects a value with no dot separator', function (): void {
    expect(runRule('no-dot-here'))->not->toBeNull();
});

it('rejects an attestation whose signature segment is not valid base64url', function (): void {
    [$payload] = explode('.', mintAttestation());

    expect(runRule($payload.'.!!!not-base64!!!'))->not->toBeNull();
});

it('rejects an attestation whose payload segment is not valid base64url', function (): void {
    [, $sig] = explode('.', mintAttestation());

    expect(runRule('!!!not-base64!!!.'.$sig))->not->toBeNull();
});

it('rejects an attestation whose payload is not valid JSON', function (): void {
    $bogusPayload = base64UrlEncode('this is not json');
    $sig = base64UrlEncode(hash_hmac('sha256', $bogusPayload, 'test_secret_key', binary: true));

    expect(runRule($bogusPayload.'.'.$sig))->not->toBeNull();
});

it('rejects an attestation whose payload is JSON but not an object', function (): void {
    $bogusPayload = base64UrlEncode('"just a string"');
    $sig = base64UrlEncode(hash_hmac('sha256', $bogusPayload, 'test_secret_key', binary: true));

    expect(runRule($bogusPayload.'.'.$sig))->not->toBeNull();
});

// ─── Signature ────────────────────────────────────────────────────────────────

it('rejects an attestation signed by an unknown secret', function (): void {
    expect(runRule(mintAttestation(secret: 'wrong_secret')))->not->toBeNull();
});

it('rejects an attestation when no secret keys are configured', function (): void {
    config(['captchaapi.secret_keys' => []]);

    expect(runRule(mintAttestation()))->not->toBeNull();
});

it('rejects an attestation whose signature has been tampered with', function (): void {
    [$payload, $sig] = explode('.', mintAttestation());
    $tamperedSig = strtoupper($sig);   // case-sensitive base64url → constant-time fail

    expect(runRule($payload.'.'.$tamperedSig))->not->toBeNull();
});

// ─── Expiry ───────────────────────────────────────────────────────────────────

it('rejects an attestation whose exp has passed', function (): void {
    expect(runRule(mintAttestation(['exp' => time() - 1])))->not->toBeNull();
});

it('rejects an attestation with no exp field', function (): void {
    $payload = ['sk' => 'test_site_key', 'iat' => time(), 'jti' => 'x', 'ol' => false];
    $payloadB64 = base64UrlEncode((string) json_encode($payload));
    $sig = base64UrlEncode(hash_hmac('sha256', $payloadB64, 'test_secret_key', binary: true));

    expect(runRule($payloadB64.'.'.$sig))->not->toBeNull();
});

it('rejects an attestation whose exp is not an integer', function (): void {
    expect(runRule(mintAttestation(['exp' => 'tomorrow'])))->not->toBeNull();
});

// ─── Site key match ───────────────────────────────────────────────────────────

it('rejects an attestation whose sk does not match the configured site key', function (): void {
    expect(runRule(mintAttestation(['sk' => 'different_site_key'])))->not->toBeNull();
});

it('rejects an attestation when the configured site key is empty', function (): void {
    config(['captchaapi.site_key' => '']);

    expect(runRule(mintAttestation()))->not->toBeNull();
});

it('rejects an attestation whose sk is missing', function (): void {
    $payload = ['exp' => time() + 300, 'iat' => time(), 'jti' => 'x', 'ol' => false];
    $payloadB64 = base64UrlEncode((string) json_encode($payload));
    $sig = base64UrlEncode(hash_hmac('sha256', $payloadB64, 'test_secret_key', binary: true));

    expect(runRule($payloadB64.'.'.$sig))->not->toBeNull();
});

// ─── Replay protection ────────────────────────────────────────────────────────

it('rejects a second submission of the same attestation when replay protection is on', function (): void {
    $attestation = mintAttestation();

    expect(runRule($attestation))->toBeNull();
    expect(runRule($attestation))->not->toBeNull();
});

it('accepts a second submission of the same attestation when replay protection is off', function (): void {
    config(['captchaapi.replay_protection' => false]);
    $attestation = mintAttestation();

    expect(runRule($attestation))->toBeNull();
    expect(runRule($attestation))->toBeNull();
});

it('treats attestations without a jti as accepted (no replay key to cache)', function (): void {
    $payload = ['sk' => 'test_site_key', 'iat' => time(), 'exp' => time() + 300, 'ol' => false];
    $payloadB64 = base64UrlEncode((string) json_encode($payload));
    $sig = base64UrlEncode(hash_hmac('sha256', $payloadB64, 'test_secret_key', binary: true));

    expect(runRule($payloadB64.'.'.$sig))->toBeNull();
});

it('uses the configured cache prefix when caching jtis', function (): void {
    config(['captchaapi.cache_prefix' => 'custom-prefix:']);
    $attestation = mintAttestation(['jti' => 'fixed-jti']);

    runRule($attestation);

    expect(Cache::has('custom-prefix:fixed-jti'))->toBeTrue();
});

// ─── Fake mode ────────────────────────────────────────────────────────────────

it('bypasses every check when Captchaapi::fake() is enabled', function (): void {
    Captchaapi::fake();

    expect(runRule('this is obvious garbage'))->toBeNull();
    expect(runRule(mintAttestation(['sk' => 'wrong'])))->toBeNull();
    expect(runRule(mintAttestation(['exp' => time() - 9999])))->toBeNull();
});

// ─── Rule helper ──────────────────────────────────────────────────────────────

/** Returns null on success, the failure message string on failure. */
function runRule(mixed $value): ?string
{
    $error = null;
    $rule = new ValidCaptcha;
    $rule->validate('captcha_attestation', $value, function ($message) use (&$error): void {
        $error = (string) $message;
    });

    return $error;
}
