<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->when($request->in_stock, fn($q) => $q->where('stock', '>', 0))
            ->orderBy('id')
            ->get();

        return response()->json([
            'count' => $products->count(),
            'data' => $products,
        ]);
    }
}
