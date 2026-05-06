<?php

declare(strict_types=1);

namespace Captchaapi\Laravel\Tests\Helpers;

final class Attestation
{
    /**
     * @param  array<string, mixed>  $overrides  Override default payload fields (sk, iat, exp, jti, ol).
     */
    public static function mint(array $overrides = [], string $secret = 'test_secret_key'): string
    {
        $payload = array_merge([
            'sk' => 'test_site_key',
            'iat' => time(),
            'exp' => time() + 300,
            'jti' => 'test-jti-'.bin2hex(random_bytes(8)),
            'ol' => false,
        ], $overrides);

        $payloadB64 = self::base64UrlEncode((string) json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $payloadB64, $secret, binary: true);
        $sigB64 = self::base64UrlEncode($signature);

        return $payloadB64.'.'.$sigB64;
    }

    public static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
