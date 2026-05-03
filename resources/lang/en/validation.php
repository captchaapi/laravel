<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | CAPTCHA validation message
    |--------------------------------------------------------------------------
    |
    | Shown when ValidCaptcha rejects an attestation. Deliberately generic —
    | revealing whether the failure was a bad signature, expired payload, or
    | replay attempt would help an attacker probe the boundary.
    |
    */

    'failed' => 'CAPTCHA verification failed. Please refresh the page and try again.',

];
