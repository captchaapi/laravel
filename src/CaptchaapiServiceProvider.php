<?php

declare(strict_types=1);

namespace Captchaapi\Laravel;

use Captchaapi\Laravel\Rules\ValidCaptcha;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\PotentiallyTranslatedString;

final class CaptchaapiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/captchaapi.php', 'captchaapi');

        $this->app->singleton(Captchaapi::class, fn (): Captchaapi => new Captchaapi);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'captchaapi');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'captchaapi');

        $this->registerBladeComponents();
        $this->registerValidationAlias();
        $this->registerPublishing();
    }

    private function registerBladeComponents(): void
    {
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'captchaapi');
    }

    /**
     * Registers the `captcha` string alias for `'rules' => 'required|captcha'`.
     *
     * extend()'s 4th argument overrides any per-failure $fail($message); replace
     * with custom validator hooks if differentiated messages are added.
     */
    private function registerValidationAlias(): void
    {
        /** @var ValidatorFactory $factory */
        $factory = $this->app->make('validator');

        $factory->extend('captcha', function (string $attribute, mixed $value): bool {
            $passed = true;
            (new ValidCaptcha)->validate(
                $attribute,
                $value,
                function (string $message, ?string $attribute = null) use (&$passed): PotentiallyTranslatedString {
                    $passed = false;

                    return new PotentiallyTranslatedString($message, app('translator'));
                },
            );

            return $passed;
        }, (string) trans('captchaapi::validation.failed'));
    }

    private function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/captchaapi.php' => config_path('captchaapi.php'),
        ], 'captchaapi-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/captchaapi'),
        ], 'captchaapi-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/captchaapi'),
        ], 'captchaapi-lang');
    }
}
