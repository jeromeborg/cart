<?php

namespace Jeromeborg\Cart\Tests;

use Jeromeborg\Cart\CartServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [CartServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cart.tax', 20);
        $app['config']->set('cart.remove_on_logout', true);
        $app['config']->set('cart.vat', false);
    }
}
