<?php

namespace Jeromeborg\Cart;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cart.php', 'cart');

        $this->app->singleton('cart', function ($app) {
            return new Cart($app['session']);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/cart.php' => config_path('cart.php'),
        ], 'cart-config');

        if (config('cart.remove_on_logout')) {
            $this->app['events']->listen(Logout::class, function () {
                $this->app->make('cart')->destroy();
            });
        }
    }
}
