<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class,'index']);

Route::post('/cart', [CartController::class,'create']);

Route::middleware('valid.cart')->group(function () {
    Route::get('/cart/{cart_token}', [CartController::class,'show']);
    Route::get('/cart/{cart_token}/recommendation', [CartController::class,'recommendation']);
    Route::post('/cart/{cart_token}', [CartController::class,'add']);
    Route::delete('/cart/{cart_token}', [CartController::class,'remove']);
});

Route::post('/orders', [OrderController::class,'store']);
