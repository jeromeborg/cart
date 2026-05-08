<?php

namespace Jeromeborg\Cart;

use Illuminate\Database\Eloquent\Model;

class CartItem
{
    public function __construct(
        public readonly string $id,
        public ?string $name,
        public int|float $quantity,
        public float $price,
        public ?Model $model = null,
        public ?float $tax = null,
    ) {}

    public function taxRate(): float
    {
        return $this->tax ?? (float) config('cart.tax', 0);
    }

    public function subtotal(): float
    {
        return $this->price * $this->quantity;
    }

    public function total(): float
    {
        return $this->subtotal() * (1 + $this->taxRate() / 100);
    }
}
