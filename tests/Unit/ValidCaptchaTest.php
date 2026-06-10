<?php

declare(strict_types=1);

use Captchaapi\Laravel\Facades\Captchaapi;
use Captchaapi\Laravel\Rules\ValidCaptcha;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Captchaapi::unfake();
});

const VERIFY_URL = 'https://captchaapi.eu/api/v1/captcha/verify';

function fakeVerify(array $body, int $status = 200): void
{
    Http::fake([VERIFY_URL => Http::response($body, $status)]);
}

// ─── Happy path ───────────────────────────────────────────────────────────────

it('accepts a response the server verifies', function (): void {
    fakeVerify(['success' => true, 'over_limit' => false]);

    expect(runRule('token.solution'))->toBeNull();
});

it('accepts a verified response even when the project is over its monthly limit', function (): void {
    fakeVerify(['success' => true, 'over_limit' => true]);

    expect(runRule('token.solution'))->toBeNull();
});

// ─── Outgoing request shape ─────────────────────────────────────────────────────

it('posts the response to the verify endpoint with the secret as a bearer token', function (): void {
    fakeVerify(['success' => true]);

    runRule('token.solution');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === VERIFY_URL
        && $request->hasHeader('Authorization', 'Bearer test_secret_key')
        && $request['response'] === 'token.solution');
});

it('targets the configured base_url override', function (): void {
    config(['captchaapi.base_url' => 'https://proxy.example.com']);
    Http::fake(['https://proxy.example.com/api/v1/captcha/verify' => Http::response(['success' => true])]);

    expect(runRule('token.solution'))->toBeNull();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://proxy.example.com/api/v1/captcha/verify');
});

// ─── Rejection: the server says no ──────────────────────────────────────────────

it('rejects a response the server reports invalid', function (): void {
    fakeVerify(['success' => false, 'error_code' => 'invalid_solution', 'over_limit' => false]);

    expect(runRule('token.solution'))->not->toBeNull();
});

it('rejects a consumed (replayed) token', function (): void {
    fakeVerify(['success' => false, 'error_code' => 'invalid_token', 'over_limit' => false]);

    expect(runRule('token.solution'))->not->toBeNull();
});

it('rejects forged garbage that the server does not recognise', function (): void {
    fakeVerify(['success' => false, 'error_code' => 'invalid_token', 'over_limit' => false]);

    expect(runRule('totally-made-up-response'))->not->toBeNull();
});

it('rejects when the server omits the success field', function (): void {
    fakeVerify(['error_code' => 'invalid_token']);

    expect(runRule('token.solution'))->not->toBeNull();
});

it('treats a truthy-but-not-true success value as a rejection', function (): void {
    fakeVerify(['success' => 1]);

    expect(runRule('token.solution'))->not->toBeNull();
});

it('rejects a 401 from a misconfigured secret', function (): void {
    fakeVerify(['success' => false, 'error_code' => 'invalid_secret'], 401);

    expect(runRule('token.solution'))->not->toBeNull();
});

// ─── Rejection: nothing worth sending ───────────────────────────────────────────

it('rejects a non-string value without calling the server', function (): void {
    Http::fake();

    expect(runRule(['not', 'a', 'string']))->not->toBeNull();
    Http::assertNothingSent();
});

it('rejects an empty string without calling the server', function (): void {
    Http::fake();

    expect(runRule(''))->not->toBeNull();
    Http::assertNothingSent();
});

it('rejects without calling the server when no secret is configured', function (): void {
    config(['captchaapi.secret' => null]);
    Http::fake();

    expect(runRule('token.solution'))->not->toBeNull();
    Http::assertNothingSent();
});

// ─── Fail policy: unreachable / 5xx ─────────────────────────────────────────────

it('lets the submission through on a 5xx when fail_open is on (the default)', function (): void {
    fakeVerify(['error' => 'boom'], 503);

    expect(runRule('token.solution'))->toBeNull();
});

it('lets the submission through on a connection failure when fail_open is on', function (): void {
    Http::fake(fn () => throw new ConnectionException('connection timed out'));

    expect(runRule('token.solution'))->toBeNull();
});

it('rejects on a 5xx when fail_open is off', function (): void {
    config(['captchaapi.fail_open' => false]);
    fakeVerify(['error' => 'boom'], 503);

    expect(runRule('token.solution'))->toBe(unavailableMessage());
});

it('rejects on a connection failure when fail_open is off', function (): void {
    config(['captchaapi.fail_open' => false]);
    Http::fake(fn () => throw new ConnectionException('connection timed out'));

    expect(runRule('token.solution'))->toBe(unavailableMessage());
});

it('uses the try-again message for an outage, not the failure message', function (): void {
    config(['captchaapi.fail_open' => false]);
    fakeVerify(['error' => 'boom'], 503);

    expect(runRule('token.solution'))
        ->toBe(unavailableMessage())
        ->not->toBe(failureMessage());
});

// ─── No retry (single-use token) ────────────────────────────────────────────────

it('makes exactly one verify call on a 5xx — a retry would burn the single-use token', function (): void {
    config(['captchaapi.fail_open' => false]);
    fakeVerify(['error' => 'boom'], 503);

    runRule('token.solution');

    Http::assertSentCount(1);
});

it('makes exactly one verify call on success', function (): void {
    fakeVerify(['success' => true]);

    runRule('token.solution');

    Http::assertSentCount(1);
});

it('memoizes a success so a repeated validation in the same request skips the verify call', function (): void {
    // Fortify validates twice per request; the second pass must not re-verify a
    // single-use response the server already consumed.
    fakeVerify(['success' => true]);

    expect(runRule('token.solution'))->toBeNull();
    expect(runRule('token.solution'))->toBeNull();

    Http::assertSentCount(1);
});

// ─── Bypass ─────────────────────────────────────────────────────────────────────

it('bypasses verification and sends nothing when Captchaapi::fake() is enabled', function (): void {
    Captchaapi::fake();
    Http::fake();

    expect(runRule('obvious garbage'))->toBeNull();
    Http::assertNothingSent();
});

it('passes silently and sends nothing when captchaapi.enabled is false', function (): void {
    config(['captchaapi.enabled' => false]);
    Http::fake();

    expect(runRule('obvious garbage'))->toBeNull();
    Http::assertNothingSent();
});

// ─── Helpers ────────────────────────────────────────────────────────────────────

/** Returns null on success, the failure message string on failure. */
function runRule(mixed $value): ?string
{
    $error = null;
    (new ValidCaptcha)->validate('captchaapi_response', $value, function ($message) use (&$error): void {
        $error = (string) $message;
    });

    return $error;
}

function failureMessage(): string
{
    return (string) trans('captchaapi::validation.failed');
}

function unavailableMessage(): string
{
    return (string) trans('captchaapi::validation.unavailable');
}
