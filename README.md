# jeromeborg/cart

A Laravel shopping cart package, compatible with Laravel 12 and 13.

## Installation

```bash
composer require jeromeborg/cart
```

The service provider and facade are registered automatically via Laravel package discovery.

### Publishing the configuration

```bash
php artisan vendor:publish --tag=cart-config
```

This publishes `config/cart.php` to your application:

```php
return [
    'remove_on_logout' => true, // clear the cart on user logout
    'tax'              => 20,   // default tax rate (percentage)
    'vat'              => false,
];
```

## Usage

The cart is accessible via the `Cart` facade or dependency injection (`Jeromeborg\Cart\Cart`).

### Adding an item

`add()` returns a `CartItem` with an auto-generated `id` you can use for subsequent operations.

```php
use Cart;

// Minimal
$item = Cart::add(price: 29.99, quantity: 1);

// Full
$item = Cart::add(
    price:    29.99,
    quantity: 2,
    name:     'Blue t-shirt',
    model:    $product,  // Eloquent model instance (optional)
    tax:      5.5,       // rate in % — overrides the config value (optional)
);

$item->id; // auto-generated identifier
```

> **Deduplication:** if a `model` is provided and an item with the same model already exists in the cart, its quantity is incremented and the same `CartItem` is returned.

### Removing an item

```php
// Decrement quantity by 1
Cart::remove($item->id);

// Decrement by a specific quantity
Cart::remove($item->id, 3);
```

> The line is automatically removed when its quantity reaches zero.

### Clearing the cart

```php
Cart::destroy();
```

### Browsing the cart

```php
// All lines (Collection of CartItem)
$items = Cart::content();

// A single line by its id
$found = Cart::find($item->id); // null if not found
```

### Totals

```php
Cart::subtotal(); // total excluding tax (float)
Cart::total();    // total including tax (float)
```

### CartItem

Each cart line is a `Jeromeborg\Cart\CartItem` object with the following properties:

| Property   | Type         | Description                               |
|------------|--------------|-------------------------------------------|
| `id`       | `string`     | Auto-generated unique identifier          |
| `name`     | `?string`    | Item name                                 |
| `quantity` | `int\|float` | Quantity                                  |
| `price`    | `float`      | Unit price (excluding tax)                |
| `model`    | `?Model`     | Associated Eloquent model instance        |
| `tax`      | `?float`     | Tax rate in % (`null` uses config value)  |

Methods available on `CartItem`:

```php
$item->taxRate();  // effective rate (item->tax ?? config('cart.tax'))
$item->subtotal(); // price × quantity
$item->total();    // subtotal × (1 + taxRate / 100)
```

## Testing

```bash
./vendor/bin/phpunit
```
