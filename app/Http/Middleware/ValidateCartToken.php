<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateCartToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): JsonResponse|Response
    {
        $cartToken = $request->route('cart_token');

        $cartExists = $cartToken && Cart::whereKey($cartToken)->exists();

        if (! $cartExists) {
            return response()->json([
                'message' => 'Invalid cart token',
            ], 404);
        }

        return $next($request);
    }
}

