<?php

namespace Jeromeborg\Cart;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;

class Cart
{
    protected const SESSION_KEY = 'cart';

    public function __construct(
        protected SessionManager $session,
    ) {}

    public function add(
        float $price,
        int|float $quantity = 1,
        ?string $name = null,
        ?Model $model = null,
        ?float $tax = null,
    ): CartItem {
        $id = $this->generateId($model);
        $items = $this->getItems();

        if ($model !== null && isset($items[$id])) {
            $items[$id]->quantity += $quantity;
        } else {
            $items[$id] = new CartItem(
                id: $id,
                name: $name,
                quantity: $quantity,
                price: $price,
                model: $model,
                tax: $tax,
            );
        }

        $this->saveItems($items);

        return $items[$id];
    }

    public function remove(string $id, int|float $quantity = 1): void
    {
        $items = $this->getItems();

        if (!isset($items[$id])) {
            return;
        }

        $items[$id]->quantity -= $quantity;

        if ($items[$id]->quantity <= 0) {
            unset($items[$id]);
        }

        $this->saveItems($items);
    }

    public function destroy(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function content(): Collection
    {
        return collect($this->getItems());
    }

    public function find(string $id): ?CartItem
    {
        return $this->getItems()[$id] ?? null;
    }

    public function subtotal(): float
    {
        return (float) collect($this->getItems())->sum(fn(CartItem $item) => $item->subtotal());
    }

    public function total(): float
    {
        return (float) collect($this->getItems())->sum(fn(CartItem $item) => $item->total());
    }

    protected function getItems(): array
    {
        $items = $this->session->get(self::SESSION_KEY, []);

        return array_map(
            fn($item) => $item instanceof CartItem ? $item : CartItem::fromArray($item),
            $items
        );
    }

    protected function saveItems(array $items): void
    {
        $this->session->put(self::SESSION_KEY, $items);
    }

    protected function generateId(?Model $model): string
    {
        if ($model !== null) {
            return md5(get_class($model) . '_' . $model->getKey());
        }

        return md5(uniqid('', true));
    }
}
