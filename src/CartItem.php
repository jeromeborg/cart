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

    public static function fromArray(array $data): self
    {
        $model = null;
        if (!empty($data['model_class']) && !empty($data['model_key'])) {
            $model = $data['model_class']::find($data['model_key']);
        }

        return new self(
            id: $data['id'],
            name: $data['name'] ?? null,
            quantity: $data['quantity'],
            price: $data['price'],
            model: $model,
            tax: $data['tax'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'quantity'    => $this->quantity,
            'price'       => $this->price,
            'tax'         => $this->tax,
            'model_class' => $this->model ? get_class($this->model) : null,
            'model_key'   => $this->model?->getKey(),
        ];
    }

    public function __serialize(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'quantity'    => $this->quantity,
            'price'       => $this->price,
            'tax'         => $this->tax,
            'model_class' => $this->model ? get_class($this->model) : null,
            'model_key'   => $this->model?->getKey(),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id       = $data['id'];
        $this->name     = $data['name'];
        $this->quantity = $data['quantity'];
        $this->price    = $data['price'];
        $this->tax      = $data['tax'];
        $this->model    = ($data['model_class'] && $data['model_key'])
            ? $data['model_class']::find($data['model_key'])
            : null;
    }

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
