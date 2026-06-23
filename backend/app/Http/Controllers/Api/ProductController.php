<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function index()
    {
        return response()->json(['data' => $this->productService->listActive()]);
    }

    public function show(string $id)
    {
        $product = $this->productService->find($id);
        if (!$product) {
            return response()->json(['error' => 'not_found'], 404);
        }
        return response()->json(['data' => $product]);
    }
}
