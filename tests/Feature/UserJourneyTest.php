<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $matrixPath = storage_path('app/co_matrix.json');
        if (file_exists($matrixPath)) {
            unlink($matrixPath);
        }

        parent::tearDown();
    }

    public function test_user_can_checkout_after_adding_and_removing_products(): void
    {
        $products = collect([
            Product::create([
                'name' => 'Product A',
                'description' => 'Desc A',
                'price' => 25.00,
                'image_url' => 'https://example.com/a.jpg',
            ]),
            Product::create([
                'name' => 'Product B',
                'description' => 'Desc B',
                'price' => 35.00,
                'image_url' => 'https://example.com/b.jpg',
            ]),
            Product::create([
                'name' => 'Product C',
                'description' => 'Desc C',
                'price' => 45.00,
                'image_url' => 'https://example.com/c.jpg',
            ]),
        ]);

        $cartResponse = $this->postJson('/api/cart');
        $cartResponse->assertOk();
        $cartToken = $cartResponse->json('cart_token');

        $products->each(function ($product) use ($cartToken) {
            $this->postJson("/api/cart/{$cartToken}", [
                'product_id' => $product->id,
                'quantity' => 1,
            ])->assertOk();
        });

        $this->deleteJson("/api/cart/{$cartToken}", [
            'product_id' => $products->last()->id,
        ])->assertOk()
            ->assertJsonMissing(['product_id' => $products->last()->id]);

        $checkoutResponse = $this->postJson('/api/orders', [
            'cart_token' => $cartToken,
            'email' => 'customer@example.com',
        ]);

        $checkoutResponse->assertOk()
            ->assertJsonFragment(['message' => 'Order created'])
            ->assertJsonCount(2, 'order.items')
            ->assertJsonFragment(['product_id' => $products[0]->id])
            ->assertJsonFragment(['product_id' => $products[1]->id])
            ->assertJsonMissing(['product_id' => $products[2]->id]);

        $this->assertDatabaseHas('orders', ['email' => 'customer@example.com']);
        $this->assertDatabaseHas('order_items', ['product_id' => $products[0]->id]);
        $this->assertDatabaseHas('order_items', ['product_id' => $products[1]->id]);
        $this->assertDatabaseMissing('order_items', ['product_id' => $products[2]->id]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cartToken]);
    }
}

