<?php
namespace Jeromeborg\Cart\Facades;

use Illuminate\Support\Facades\Facade;

class Cart extends Facade {
    
    protected static function getFacadeAccessor(): string
    {
        return 'cart';
    }
}