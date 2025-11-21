<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CartController extends Controller
{
    public function create() {
        $cart = Cart::create();
        return response()->json(['cart_token' => $cart->id]);
    }

    public function show(string $cart_token) {
        return Cart::with('items.product')->findOrFail($cart_token);
    }

    public function recommendation(string $cart_token) {
        $cart = Cart::with('items.product')->findOrFail($cart_token);
        return (new RecommendationService())->recommendForCart($cart);
    }

    public function add(Request $request, string $cart_token): JsonResponse
    {
        $cart = Cart::findOrFail($cart_token);
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity',1);

        $item = $cart->items()->firstOrCreate(
            ['product_id'=>$productId],
            ['quantity' => 0]
        );
        $item->quantity += $quantity;
        $item->save();

        return response()->json($cart->load('items.product'));
    }

    public function remove(Request $request, string $cart_token): JsonResponse
    {
        $cart = Cart::findOrFail($cart_token);
        $productId = $request->input('product_id');

        $cart->items()->where('product_id',$productId)->delete();

        return response()->json($cart->load('items.product'));
    }
}
