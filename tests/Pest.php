<?php

declare(strict_types=1);

use Captchaapi\Laravel\Tests\TestCase;

uses(TestCase::class)->in(__DIR__.'/Feature', __DIR__.'/Unit');

/**
 * Mint a real attestation in the format produced by captchaapi.eu's
 * /api/v1/captcha/verify endpoint, signed with one of the configured
 * test secret keys. Allows ValidCaptcha to be exercised end-to-end
 * without standing up a real PoW flow.
 *
 * @param  array<string, mixed>  $overrides  Override default payload fields
 *                                           (sk, iat, exp, jti, ol).
 */
function mintAttestation(array $overrides = [], string $secret = 'test_secret_key'): string
{
    $payload = array_merge([
        'sk' => 'test_site_key',
        'iat' => time(),
        'exp' => time() + 300,
        'jti' => 'test-jti-'.bin2hex(random_bytes(8)),
        'ol' => false,
    ], $overrides);

    $payloadB64 = base64UrlEncode((string) json_encode($payload, JSON_UNESCAPED_SLASHES));
    $signature = hash_hmac('sha256', $payloadB64, $secret, binary: true);
    $sigB64 = base64UrlEncode($signature);

    return $payloadB64.'.'.$sigB64;
}

function base64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}
