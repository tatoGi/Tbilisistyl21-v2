<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOrderTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveProduct(): Product
    {
        $product = Product::create([
            'title' => ['ka' => 'მაისური', 'en' => 'T-Shirt'],
            'description' => ['ka' => 'აღწერა'],
            'price_gel' => 45,
            'category' => 'apparel',
            'status' => 'active',
        ]);

        ProductSize::create([
            'product_id' => $product->id,
            'size' => 'M',
            'quantity' => 10,
        ]);

        ProductSize::create([
            'product_id' => $product->id,
            'size' => 'L',
            'quantity' => 0, // sold out
        ]);

        return $product;
    }

    public function test_create_product_order_validates_input(): void
    {
        $response = $this->postJson('/api/orders/products', []);
        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'productId', 'size', 'name', 'surname', 'personalNumber', 'email', 'phone',
            ]);
    }

    public function test_create_product_order_rejects_unavailable_product(): void
    {
        $product = Product::create([
            'title' => ['ka' => 'Test'],
            'price_gel' => 45,
            'category' => 'apparel',
            'status' => 'draft', // not active
        ]);

        ProductSize::create([
            'product_id' => $product->id,
            'size' => 'M',
            'quantity' => 10,
        ]);

        $response = $this->postJson('/api/orders/products', [
            'productId' => $product->id,
            'size' => 'M',
            'name' => 'John',
            'surname' => 'Doe',
            'personalNumber' => '01001234567',
            'email' => 'john@test.com',
            'phone' => '+995555123456',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'product_unavailable');
    }

    public function test_create_product_order_rejects_sold_out_size(): void
    {
        $product = $this->createActiveProduct();

        $response = $this->postJson('/api/orders/products', [
            'productId' => $product->id,
            'size' => 'L', // quantity = 0
            'name' => 'John',
            'surname' => 'Doe',
            'personalNumber' => '01001234567',
            'email' => 'john@test.com',
            'phone' => '+995555123456',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'size_sold_out');
    }

    public function test_create_product_order_rejects_nonexistent_size(): void
    {
        $product = $this->createActiveProduct();

        $response = $this->postJson('/api/orders/products', [
            'productId' => $product->id,
            'size' => 'XXL', // doesn't exist
            'name' => 'John',
            'surname' => 'Doe',
            'personalNumber' => '01001234567',
            'email' => 'john@test.com',
            'phone' => '+995555123456',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'size_sold_out');
    }
}
