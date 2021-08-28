<?php

namespace Scyllaly\HCaptcha;

use Illuminate\Support\ServiceProvider;

class HCaptchaServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $app = $this->app;

        $this->bootConfig();

        $app['validator']->extend('HCaptcha', function ($attribute, $value) use ($app) {
            return $app['HCaptcha']->verifyResponse($value, $app['request']->getClientIp());
        });

        if ($app->bound('form')) {
            $app['form']->macro('HCaptcha', function ($attributes = []) use ($app) {
                return $app['HCaptcha']->display($attributes, $app->getLocale());
            });
        }
    }

    /**
     * Booting configure.
     */
    protected function bootConfig()
    {
        $path = __DIR__ . '/config/config.php';

        $this->mergeConfigFrom($path, 'HCaptcha');

        if (function_exists('config_path')) {
            $this->publishes([$path => config_path('HCaptcha.php')]);
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton('HCaptcha', function ($app) {
            if ($app['config']['HCaptcha.server-get-config']) {
                $hCaptcha = \App\Components\CaptchaVerify::hCaptchaGetConfig();
                return new HCaptcha(
                    $hCaptcha['secret'],
                    $hCaptcha['sitekey'],
                    $hCaptcha['options']
                );
            } else {
                return new HCaptcha(
                    $app['config']['HCaptcha.secret'],
                    $app['config']['HCaptcha.sitekey'],
                    $app['config']['HCaptcha.options']
                );
            }
        });

        $this->app->alias('HCaptcha', HCaptcha::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['HCaptcha'];
    }
}
