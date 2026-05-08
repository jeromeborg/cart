<?php

namespace Jeromeborg\Cart\Tests;

use Illuminate\Database\Eloquent\Model;
use Jeromeborg\Cart\Cart;
use Jeromeborg\Cart\CartItem;

class CartTest extends TestCase
{
    private Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cart = $this->app->make('cart');
    }

    public function test_add_item_appears_in_content(): void
    {
        $item = $this->cart->add(10.0, 2, 'Product 1');

        $content = $this->cart->content();
        $this->assertCount(1, $content);
        $this->assertEquals(2, $content->first()->quantity);
        $this->assertNotEmpty($item->id);
    }

    public function test_add_without_model_always_creates_new_line(): void
    {
        $this->cart->add(10.0, 1);
        $this->cart->add(10.0, 1);

        $this->assertCount(2, $this->cart->content());
    }

    public function test_add_same_model_increments_quantity(): void
    {
        $model = $this->makeModel(1);

        $this->cart->add(10.0, 1, null, $model);
        $this->cart->add(10.0, 3, null, $model);

        $content = $this->cart->content();
        $this->assertCount(1, $content);
        $this->assertEquals(4, $content->first()->quantity);
    }

    public function test_add_different_models_creates_separate_lines(): void
    {
        $this->cart->add(10.0, 1, null, $this->makeModel(1));
        $this->cart->add(20.0, 1, null, $this->makeModel(2));

        $this->assertCount(2, $this->cart->content());
    }

    public function test_add_returns_cartitem_with_generated_id(): void
    {
        $item1 = $this->cart->add(10.0, 1);
        $item2 = $this->cart->add(10.0, 1);

        $this->assertNotEmpty($item1->id);
        $this->assertNotEmpty($item2->id);
        $this->assertNotEquals($item1->id, $item2->id);
    }

    public function test_add_same_model_returns_stable_id(): void
    {
        $model = $this->makeModel(1);

        $item1 = $this->cart->add(10.0, 1, null, $model);
        $item2 = $this->cart->add(10.0, 1, null, $model);

        $this->assertEquals($item1->id, $item2->id);
    }

    public function test_remove_decrements_quantity(): void
    {
        $item = $this->cart->add(10.0, 5);
        $this->cart->remove($item->id, 2);

        $this->assertEquals(3, $this->cart->find($item->id)->quantity);
    }

    public function test_remove_deletes_line_when_quantity_reaches_zero(): void
    {
        $item = $this->cart->add(10.0, 2);
        $this->cart->remove($item->id, 2);

        $this->assertCount(0, $this->cart->content());
    }

    public function test_remove_deletes_line_when_quantity_goes_negative(): void
    {
        $item = $this->cart->add(10.0, 1);
        $this->cart->remove($item->id, 5);

        $this->assertCount(0, $this->cart->content());
    }

    public function test_remove_model_item_by_id(): void
    {
        $item = $this->cart->add(10.0, 3, null, $this->makeModel(1));
        $this->cart->remove($item->id, 1);

        $this->assertEquals(2, $this->cart->find($item->id)->quantity);
    }

    public function test_remove_nonexistent_id_does_nothing(): void
    {
        $this->cart->add(10.0, 1);
        $this->cart->remove('does-not-exist');

        $this->assertCount(1, $this->cart->content());
    }

    public function test_destroy_empties_cart(): void
    {
        $this->cart->add(10.0, 1);
        $this->cart->add(20.0, 2);

        $this->cart->destroy();

        $this->assertCount(0, $this->cart->content());
    }

    public function test_find_by_id_returns_correct_item(): void
    {
        $this->cart->add(10.0, 1, 'First');
        $item2 = $this->cart->add(20.0, 1, 'Second');

        $found = $this->cart->find($item2->id);

        $this->assertNotNull($found);
        $this->assertEquals('Second', $found->name);
        $this->assertEquals(20.0, $found->price);
    }

    public function test_find_returns_null_when_not_found(): void
    {
        $this->assertNull($this->cart->find('nope'));
    }

    public function test_subtotal_sums_price_times_quantity(): void
    {
        $this->cart->add(10.0, 2); // 20
        $this->cart->add(5.0, 4);  // 20

        $this->assertEquals(40.0, $this->cart->subtotal());
    }

    public function test_total_applies_default_tax(): void
    {
        $this->cart->add(100.0, 1); // 100 * 1.20 = 120

        $this->assertEquals(120.0, $this->cart->total());
    }

    public function test_total_applies_item_tax_over_default(): void
    {
        $this->cart->add(100.0, 1, null, null, 10.0); // 10% tax → 110

        $this->assertEqualsWithDelta(110.0, $this->cart->total(), 0.0001);
    }

    public function test_total_mixes_item_and_default_tax(): void
    {
        $this->cart->add(100.0, 1, null, null, 10.0); // 110
        $this->cart->add(100.0, 1);                   // 120 (default 20%)

        $this->assertEqualsWithDelta(230.0, $this->cart->total(), 0.0001);
    }

    public function test_item_without_tax_uses_config_tax(): void
    {
        $item = $this->cart->add(100.0, 1);

        $this->assertEquals(20.0, $item->taxRate());
    }

    public function test_item_with_tax_uses_its_own_rate(): void
    {
        $item = $this->cart->add(100.0, 1, null, null, 5.5);

        $this->assertEquals(5.5, $item->taxRate());
    }

    public function test_cartitem_subtotal(): void
    {
        $item = new CartItem('row1', 'Product', 3, 15.0);

        $this->assertEquals(45.0, $item->subtotal());
    }

    public function test_cartitem_total_with_tax(): void
    {
        $item = new CartItem('row1', 'Product', 2, 50.0, null, 10.0);

        $this->assertEqualsWithDelta(110.0, $item->total(), 0.0001);
    }

    private function makeModel(int $id): Model
    {
        $model = $this->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->getMock();

        $model->method('getKey')->willReturn($id);

        return $model;
    }
}
