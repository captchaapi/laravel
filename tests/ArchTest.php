<?php

declare(strict_types=1);

use Captchaapi\Laravel\Concerns\WithCaptcha;
use Illuminate\Contracts\Validation\ValidationRule;

arch('strict types are declared in every src file')
    ->expect('Captchaapi\Laravel')
    ->toUseStrictTypes();

arch('classes in src are final unless explicitly extensible')
    ->expect('Captchaapi\Laravel')
    ->classes()
    ->toBeFinal()
    ->ignoring([
        WithCaptcha::class,  // trait, not a class
    ]);

arch('no debug helpers leak into shipped code')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('Rules namespace contains validation rules')
    ->expect('Captchaapi\Laravel\Rules')
    ->toImplement(ValidationRule::class);
