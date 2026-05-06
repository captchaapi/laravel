<?php

declare(strict_types=1);

use Captchaapi\Laravel\Facades\Captchaapi;
use Captchaapi\Laravel\Rules\ValidCaptcha;
use Captchaapi\Laravel\Tests\Helpers\Attestation;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\ParameterBag;

beforeEach(function (): void {
    Captchaapi::unfake();
    Cache::flush();
});

// ─── Happy path ───────────────────────────────────────────────────────────────

it('accepts a freshly minted attestation', function (): void {
    expect(runRule(Attestation::mint()))->toBeNull();
});

it('accepts an attestation signed by a non-primary secret in the rotation list', function (): void {
    config(['captchaapi.secret_keys' => ['stale_primary', 'test_secret_key']]);

    expect(runRule(Attestation::mint()))->toBeNull();
});

// ─── Format validation ────────────────────────────────────────────────────────

it('rejects a value that is not a string', function (): void {
    expect(runRule(['not', 'a', 'string']))->not->toBeNull();
});

it('rejects a value with no dot separator', function (): void {
    expect(runRule('no-dot-here'))->not->toBeNull();
});

it('rejects an attestation whose signature segment is not valid base64url', function (): void {
    [$payload] = explode('.', Attestation::mint());

    expect(runRule($payload.'.!!!not-base64!!!'))->not->toBeNull();
});

it('rejects an attestation whose payload segment is not valid base64url', function (): void {
    [, $sig] = explode('.', Attestation::mint());

    expect(runRule('!!!not-base64!!!.'.$sig))->not->toBeNull();
});

it('rejects an attestation whose payload is non-base64url even when the signature matches', function (): void {
    // Sign garbage payload so signature passes but base64 decode fails downstream.
    $bogusPayload = 'aaa!aaa';
    $sig = Attestation::base64UrlEncode(hash_hmac('sha256', $bogusPayload, 'test_secret_key', binary: true));

    expect(runRule($bogusPayload.'.'.$sig))->not->toBeNull();
});

it('rejects an attestation whose payload is not valid JSON', function (): void {
    $bogusPayload = Attestation::base64UrlEncode('this is not json');
    $sig = Attestation::base64UrlEncode(hash_hmac('sha256', $bogusPayload, 'test_secret_key', binary: true));

    expect(runRule($bogusPayload.'.'.$sig))->not->toBeNull();
});

it('rejects an attestation whose payload is JSON but not an object', function (): void {
    $bogusPayload = Attestation::base64UrlEncode('"just a string"');
    $sig = Attestation::base64UrlEncode(hash_hmac('sha256', $bogusPayload, 'test_secret_key', binary: true));

    expect(runRule($bogusPayload.'.'.$sig))->not->toBeNull();
});

// ─── Signature ────────────────────────────────────────────────────────────────

it('rejects an attestation signed by an unknown secret', function (): void {
    expect(runRule(Attestation::mint(secret: 'wrong_secret')))->not->toBeNull();
});

it('rejects an attestation when no secret keys are configured', function (): void {
    config(['captchaapi.secret_keys' => []]);

    expect(runRule(Attestation::mint()))->not->toBeNull();
});

it('rejects an attestation whose signature has been tampered with', function (): void {
    [$payload, $sig] = explode('.', Attestation::mint());
    $tamperedSig = strtoupper($sig);   // case-sensitive base64url → constant-time fail

    expect(runRule($payload.'.'.$tamperedSig))->not->toBeNull();
});

// ─── Expiry ───────────────────────────────────────────────────────────────────

it('rejects an attestation whose exp has passed', function (): void {
    expect(runRule(Attestation::mint(['exp' => time() - 1])))->not->toBeNull();
});

it('rejects an attestation with no exp field', function (): void {
    $payload = ['sk' => 'test_site_key', 'iat' => time(), 'jti' => 'x', 'ol' => false];
    $payloadB64 = Attestation::base64UrlEncode((string) json_encode($payload));
    $sig = Attestation::base64UrlEncode(hash_hmac('sha256', $payloadB64, 'test_secret_key', binary: true));

    expect(runRule($payloadB64.'.'.$sig))->not->toBeNull();
});

it('rejects an attestation whose exp is not an integer', function (): void {
    expect(runRule(Attestation::mint(['exp' => 'tomorrow'])))->not->toBeNull();
});

// ─── Issued-at / clock skew ───────────────────────────────────────────────────

it('rejects an attestation whose iat is missing', function (): void {
    $payload = ['sk' => 'test_site_key', 'exp' => time() + 300, 'jti' => 'x', 'ol' => false];
    $payloadB64 = Attestation::base64UrlEncode((string) json_encode($payload));
    $sig = Attestation::base64UrlEncode(hash_hmac('sha256', $payloadB64, 'test_secret_key', binary: true));

    expect(runRule($payloadB64.'.'.$sig))->not->toBeNull();
});

it('rejects an attestation whose iat is not an integer', function (): void {
    expect(runRule(Attestation::mint(['iat' => 'now'])))->not->toBeNull();
});

it('rejects an attestation whose iat is far in the future (clock-skew abuse)', function (): void {
    expect(runRule(Attestation::mint(['iat' => time() + 3600])))->not->toBeNull();
});

it('accepts an attestation whose iat is slightly in the future (within leeway)', function (): void {
    config(['captchaapi.clock_skew_leeway' => 60]);

    expect(runRule(Attestation::mint(['iat' => time() + 30])))->toBeNull();
});

it('respects a custom clock-skew leeway from config', function (): void {
    config(['captchaapi.clock_skew_leeway' => 10]);

    expect(runRule(Attestation::mint(['iat' => time() + 30])))->not->toBeNull();
});

// ─── Site key match ───────────────────────────────────────────────────────────

it('rejects an attestation whose sk does not match the configured site key', function (): void {
    expect(runRule(Attestation::mint(['sk' => 'different_site_key'])))->not->toBeNull();
});

it('rejects an attestation when the configured site key is empty', function (): void {
    config(['captchaapi.site_key' => '']);

    expect(runRule(Attestation::mint()))->not->toBeNull();
});

it('rejects an attestation whose sk is missing', function (): void {
    $payload = ['exp' => time() + 300, 'iat' => time(), 'jti' => 'x', 'ol' => false];
    $payloadB64 = Attestation::base64UrlEncode((string) json_encode($payload));
    $sig = Attestation::base64UrlEncode(hash_hmac('sha256', $payloadB64, 'test_secret_key', binary: true));

    expect(runRule($payloadB64.'.'.$sig))->not->toBeNull();
});

// ─── Replay protection ────────────────────────────────────────────────────────

it('rejects a second submission of the same attestation across requests when replay protection is on', function (): void {
    $attestation = Attestation::mint();

    expect(runRule($attestation))->toBeNull();

    // Reset per-request memoization so the jti cache claim is what's tested.
    resetRequest();

    expect(runRule($attestation))->not->toBeNull();
});

it('memoizes successful validation per request — same attestation passes twice within a request', function (): void {
    $attestation = Attestation::mint();

    expect(runRule($attestation))->toBeNull();
    expect(runRule($attestation))->toBeNull();
    expect(runRule($attestation))->toBeNull();
});

it('memoization is per-attestation — different attestations validate independently within one request', function (): void {
    $a = Attestation::mint();
    $b = Attestation::mint();

    expect(runRule($a))->toBeNull();
    expect(runRule($b))->toBeNull();
});

it('failed validation does not memoize — a subsequent call gets fully checked again', function (): void {
    $bad = Attestation::mint(secret: 'wrong_secret');

    expect(runRule($bad))->not->toBeNull();
    expect(runRule($bad))->not->toBeNull();
});

it('accepts a second submission of the same attestation when replay protection is off', function (): void {
    config(['captchaapi.replay_protection' => false]);
    $attestation = Attestation::mint();

    expect(runRule($attestation))->toBeNull();
    expect(runRule($attestation))->toBeNull();
});

it('treats attestations without a jti as accepted (no replay key to cache)', function (): void {
    $payload = ['sk' => 'test_site_key', 'iat' => time(), 'exp' => time() + 300, 'ol' => false];
    $payloadB64 = Attestation::base64UrlEncode((string) json_encode($payload));
    $sig = Attestation::base64UrlEncode(hash_hmac('sha256', $payloadB64, 'test_secret_key', binary: true));

    expect(runRule($payloadB64.'.'.$sig))->toBeNull();
});

it('rejects an attestation whose jti exceeds the maximum length', function (): void {
    $attestation = Attestation::mint(['jti' => str_repeat('a', 129)]);

    expect(runRule($attestation))->not->toBeNull();
});

it('accepts an attestation whose jti sits at the maximum length', function (): void {
    $attestation = Attestation::mint(['jti' => str_repeat('a', 128)]);

    expect(runRule($attestation))->toBeNull();
});

it('uses the configured cache prefix when caching jtis', function (): void {
    config(['captchaapi.cache_prefix' => 'custom-prefix:']);
    $attestation = Attestation::mint(['jti' => 'fixed-jti']);

    runRule($attestation);

    expect(Cache::has('custom-prefix:fixed-jti'))->toBeTrue();
});

// ─── Fake mode ────────────────────────────────────────────────────────────────

it('bypasses every check when Captchaapi::fake() is enabled', function (): void {
    Captchaapi::fake();

    expect(runRule('this is obvious garbage'))->toBeNull();
    expect(runRule(Attestation::mint(['sk' => 'wrong'])))->toBeNull();
    expect(runRule(Attestation::mint(['exp' => time() - 9999])))->toBeNull();
});

// ─── Rule helper ──────────────────────────────────────────────────────────────

/** Returns null on success, the failure message string on failure. */
function runRule(mixed $value): ?string
{
    $error = null;
    $rule = new ValidCaptcha;
    $rule->validate('captcha_attestation', $value, function ($message) use (&$error): void {
        $error = $message;
    });

    return $error;
}

/** Drops the request attributes bag where ValidCaptcha stores per-request memoization. */
function resetRequest(): void
{
    request()->attributes = new ParameterBag;
}
