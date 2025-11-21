<?php

use Illuminate\Support\Facades\Artisan;
use App\Services\RecommendationService;

Artisan::command('build-recommendation', function () {

    $orders = json_decode(file_get_contents(storage_path('orders.json')), true);

    (new RecommendationService())->buildFromOrders($orders);

    $this->info('Co-occurrence matrix has been built and saved to storage/co_matrix.json');

})->purpose('Rebuild the recommendation system based on list of purchases');
