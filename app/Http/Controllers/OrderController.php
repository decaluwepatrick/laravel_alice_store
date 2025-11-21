<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrderController extends Controller
{
    public function store(Request $request) {
        $request->validate([
            'cart_token'=>'required',
            'email'=>'required|email'
        ]);

        $cart = Cart::with('items')->findOrFail($request->cart_token);
        if($cart->items->isEmpty()){
            return response()->json(['message'=>'Cart empty'],400);
        }

        $order = Order::create(['email'=>$request->email]);

        foreach($cart->items as $item){
            OrderItem::create([
                'order_id'=>$order->id,
                'product_id'=>$item->product_id,
                'quantity'=>$item->quantity
            ]);
        }

        $cart->items()->delete();

        return response()->json(['message'=>'Order created', 'order'=>$order->load('items.product')]);
    }
}
