<?php

namespace App\Actions;

use App\Jobs\SendProductOrderEmailJob;
use App\Models\Product;
use App\Models\ProductOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateWalkUpProductSaleAction
{
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $product = Product::with('sizes')->findOrFail($data['productId']);

            if ($product->status !== 'active') {
                return ['error' => 'product_unavailable', 'status' => 400];
            }

            $size = $product->sizes->where('size', $data['size'])->first();

            if (!$size || $size->quantity <= 0) {
                return ['error' => 'size_sold_out', 'status' => 400];
            }

            $decremented = DB::table('product_sizes')
                ->where('product_id', $product->id)
                ->where('size', $data['size'])
                ->where('quantity', '>', 0)
                ->decrement('quantity');

            if ($decremented === 0) {
                return ['error' => 'size_sold_out', 'status' => 400];
            }

            Cache::forget(Product::API_CACHE_KEY);

            $discount = (float) ($data['discountAmount'] ?? 0);
            $finalAmount = max(0, (float) $product->price_gel - $discount);

            $internalId = strtoupper(Str::random(8));

            $order = ProductOrder::create([
                'id' => $internalId,
                'product_id' => $product->id,
                'product_title' => $product->setLocale('ka')->title,
                'size' => $data['size'],
                'name' => $data['name'],
                'surname' => $data['surname'],
                'personal_number' => $data['personalNumber'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'amount' => $finalAmount,
                'discount_amount' => $discount > 0 ? $discount : null,
                'sold_by' => $data['soldBy'],
                'status' => 'paid',
            ]);

            SendProductOrderEmailJob::dispatch($order->id);

            return ['productOrder' => $order, 'status' => 200];
        });
    }
}
