<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | CAPTCHA validation messages
    |--------------------------------------------------------------------------
    |
    | `failed` is shown when the server rejects the response — a bad, expired,
    | or already-used token. Deliberately generic so it gives an attacker
    | nothing to probe.
    |
    | `unavailable` is shown only when fail_open is off and the verify call
    | could not reach the server. It asks the visitor to try again rather than
    | blaming them for a captcha they may well have solved.
    |
    */

    'failed' => 'CAPTCHA verification failed. Please refresh the page and try again.',

    'unavailable' => 'CAPTCHA verification is temporarily unavailable. Please try again in a moment.',

];
