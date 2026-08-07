<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentSurchargeService;
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

        $data = $product->toArray();
        $data['price_gel'] = app(PaymentSurchargeService::class)->payable((float) $product->price_gel);

        return response()->json(['data' => $data]);
    }
}
