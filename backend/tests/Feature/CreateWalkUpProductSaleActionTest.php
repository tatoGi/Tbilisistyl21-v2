<?php

namespace Tests\Feature;

use App\Actions\CreateWalkUpProductSaleAction;
use App\Jobs\SendProductOrderEmailJob;
use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CreateWalkUpProductSaleActionTest extends TestCase
{
    use RefreshDatabase;

    private function createProductWithSize(int $quantity = 5): array
    {
        $product = Product::create([
            'title' => ['ka' => 'მაისური', 'en' => 'T-shirt'],
            'description' => ['ka' => '', 'en' => ''],
            'price_gel' => 50,
            'status' => 'active',
        ]);

        $size = ProductSize::create([
            'product_id' => $product->id,
            'size' => 'M',
            'quantity' => $quantity,
        ]);

        return [$product, $size];
    }

    public function test_creates_paid_product_order_with_discount_and_seller_attribution(): void
    {
        Bus::fake();

        [$product, $size] = $this->createProductWithSize();

        $result = app(CreateWalkUpProductSaleAction::class)->execute([
            'productId' => $product->id,
            'size' => 'M',
            'name' => 'Giorgi',
            'surname' => 'Beridze',
            'personalNumber' => '01011011011',
            'email' => 'giorgi@example.com',
            'phone' => '+995500000000',
            'discountAmount' => 5,
            'soldBy' => 'Nino Seller',
        ]);

        $this->assertEquals(200, $result['status']);
        $order = $result['productOrder'];
        $this->assertEquals('paid', $order->status);
        $this->assertEquals(45, (float) $order->amount);
        $this->assertEquals(5, (float) $order->discount_amount);
        $this->assertEquals('Nino Seller', $order->sold_by);

        $size->refresh();
        $this->assertEquals(4, $size->quantity);

        Bus::assertDispatched(SendProductOrderEmailJob::class);
    }

    public function test_returns_size_sold_out_when_no_stock(): void
    {
        [$product, $size] = $this->createProductWithSize(quantity: 0);

        $result = app(CreateWalkUpProductSaleAction::class)->execute([
            'productId' => $product->id,
            'size' => 'M',
            'name' => 'Giorgi',
            'surname' => 'Beridze',
            'personalNumber' => '01011011011',
            'email' => 'giorgi@example.com',
            'phone' => '+995500000000',
            'discountAmount' => 0,
            'soldBy' => 'Nino Seller',
        ]);

        $this->assertEquals(400, $result['status']);
        $this->assertEquals('size_sold_out', $result['error']);
    }
}
