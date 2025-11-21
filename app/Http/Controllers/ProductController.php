<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    public function index(): Collection
    {
        return Product::all();
    }
}
