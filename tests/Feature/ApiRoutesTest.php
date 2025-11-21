<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRoutesTest extends TestCase
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

    public function test_products_index_route_returns_products(): void
    {
        $productA = Product::create([
            'name' => 'First Product',
            'description' => 'Desc A',
            'price' => 10.00,
            'image_url' => 'https://example.com/a.jpg',
        ]);

        $productB = Product::create([
            'name' => 'Second Product',
            'description' => 'Desc B',
            'price' => 20.00,
            'image_url' => 'https://example.com/b.jpg',
        ]);

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => $productA->name])
            ->assertJsonFragment(['name' => $productB->name]);
    }

    public function test_cart_create_route_returns_cart_token(): void
    {
        $response = $this->postJson('/api/cart');

        $response->assertOk()
            ->assertJsonStructure(['cart_token']);

        $cartId = $response->json('cart_token');

        $this->assertDatabaseHas('carts', ['id' => $cartId]);
    }

    public function test_cart_show_route_returns_cart_with_items(): void
    {
        $cart = Cart::create();
        $product = Product::create([
            'name' => 'Cart Product',
            'description' => 'Desc',
            'price' => 15.00,
            'image_url' => 'https://example.com/c.jpg',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->getJson("/api/cart/{$cart->id}");

        $response->assertOk()
            ->assertJsonFragment(['id' => $cart->id])
            ->assertJsonFragment(['product_id' => $product->id]);
    }

    public function test_cart_recommendation_route_returns_recommendations(): void
    {
        $cart = Cart::create();
        $primaryProduct = Product::create([
            'name' => 'Primary Product',
            'description' => 'Primary',
            'price' => 12.50,
            'image_url' => 'https://example.com/p.jpg',
        ]);

        $recommendedProduct = Product::create([
            'name' => 'Recommended Product',
            'description' => 'Recommended',
            'price' => 22.50,
            'image_url' => 'https://example.com/r.jpg',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $primaryProduct->id,
            'quantity' => 1,
        ]);

        file_put_contents(
            storage_path('app/co_matrix.json'),
            json_encode([$primaryProduct->id => [$recommendedProduct->id => 3]], JSON_PRETTY_PRINT)
        );

        $response = $this->getJson("/api/cart/{$cart->id}/recommendation");

        $response->assertOk()
            ->assertJsonFragment(['id' => $recommendedProduct->id])
            ->assertJsonFragment(['name' => $recommendedProduct->name]);
    }

    public function test_cart_add_route_adds_items_to_cart(): void
    {
        $cart = Cart::create();
        $product = Product::create([
            'name' => 'Add Product',
            'description' => 'Add desc',
            'price' => 9.99,
            'image_url' => 'https://example.com/add.jpg',
        ]);

        $response = $this->postJson("/api/cart/{$cart->id}", [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_cart_remove_route_removes_items_from_cart(): void
    {
        $cart = Cart::create();
        $product = Product::create([
            'name' => 'Remove Product',
            'description' => 'Remove desc',
            'price' => 14.25,
            'image_url' => 'https://example.com/remove.jpg',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response = $this->deleteJson("/api/cart/{$cart->id}", [
            'product_id' => $product->id,
        ]);

        $response->assertOk()
            ->assertJsonMissing(['product_id' => $product->id]);

        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_cart_routes_reject_invalid_cart_token(): void
    {
        $invalidToken = '00000000-0000-0000-0000-000000000000';

        $product = Product::create([
            'name' => 'Invalid Product',
            'description' => 'Desc',
            'price' => 19.00,
            'image_url' => 'https://example.com/invalid.jpg',
        ]);

        $this->getJson("/api/cart/{$invalidToken}")
            ->assertNotFound()
            ->assertJson(['message' => 'Invalid cart token']);

        $this->getJson("/api/cart/{$invalidToken}/recommendation")
            ->assertNotFound()
            ->assertJson(['message' => 'Invalid cart token']);

        $this->postJson("/api/cart/{$invalidToken}", [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertNotFound()
            ->assertJson(['message' => 'Invalid cart token']);

        $this->deleteJson("/api/cart/{$invalidToken}", [
            'product_id' => $product->id,
        ])->assertNotFound()
            ->assertJson(['message' => 'Invalid cart token']);
    }

    public function test_orders_store_route_creates_order_from_cart(): void
    {
        $cart = Cart::create();
        $product = Product::create([
            'name' => 'Order Product',
            'description' => 'Order desc',
            'price' => 30.00,
            'image_url' => 'https://example.com/order.jpg',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->postJson('/api/orders', [
            'cart_token' => $cart->id,
            'email' => 'buyer@example.com',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Order created'])
            ->assertJsonStructure([
                'message',
                'order' => [
                    'id',
                    'email',
                    'items',
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'email' => 'buyer@example.com',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);
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

