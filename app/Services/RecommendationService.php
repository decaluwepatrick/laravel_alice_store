<?php

namespace App\Services;

use App\Models\Product;

class RecommendationService
{
    protected string $matrixPath;

    public function __construct()
    {
        $this->matrixPath = storage_path('co_matrix.json');
    }

    /** Build co-occurrence matrix from orders */
    public function buildFromOrders(array $orders): array
    {
        $matrix = [];

        foreach ($orders as $order) {
            $productIds = array_column($order['products'], 'product_id');

            foreach ($productIds as $p1) {
                foreach ($productIds as $p2) {
                    if ($p1 === $p2) continue;
                    $matrix[$p1][$p2] = ($matrix[$p1][$p2] ?? 0) + 1;
                }
            }
        }

        file_put_contents($this->matrixPath, json_encode($matrix, JSON_PRETTY_PRINT));

        return $matrix;
    }

    /** Load co-occurrence matrix */
    public function loadMatrix(): array
    {
        if (!file_exists($this->matrixPath)) {
            return [];
        }

        return json_decode(file_get_contents($this->matrixPath), true);
    }

    /** Get recommendations from a cart */
    public function recommendForCart($cart, int $limit = 5)
    {
        $matrix = $this->loadMatrix();

        if (!$matrix) return collect([]);

        $cartProductIds = $cart->items->pluck('product_id')->toArray();
        $scores = [];

        foreach ($cartProductIds as $pid) {
            if (!isset($matrix[$pid])) continue;

            foreach ($matrix[$pid] as $relatedId => $score) {
                if (in_array($relatedId, $cartProductIds)) continue;

                $scores[$relatedId] = ($scores[$relatedId] ?? 0) + $score;
            }
        }

        arsort($scores);

        $recommendedIds = array_slice(array_keys($scores), 0, $limit);

        return Product::whereIn('id', $recommendedIds)->get();
    }
}
