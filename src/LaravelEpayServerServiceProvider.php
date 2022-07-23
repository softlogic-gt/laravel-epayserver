<?php
namespace SoftlogicGT\LaravelEpayServer;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class LaravelEpayServerServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot(Router $router)
    {
        $this->mergeConfigFrom(__DIR__ . '/config/laravel-epayserver.php', 'laravel-epayserver');
        $this->loadViewsFrom(__DIR__ . '/resources/views/', 'laravel-epayserver');
        $this->publishes([
            __DIR__ . '/config/laravel-epayserver.php' => config_path('laravel-epayserver.php'),
        ], 'config');
    }

    public function register()
    {
        $this->app->singleton('laravel-epay-server', function ($app) {
            return new LaravelEpayServer;
        });
    }

    public function provides()
    {
        return ['laravel-epay-server'];
    }
}
