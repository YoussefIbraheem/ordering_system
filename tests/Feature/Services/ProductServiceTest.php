<?php

use App\Models\Product;
use App\Services\ProductService;

it('creates a product with stock and price', function () {
    $product = ProductService::create('Laptop', 5, 999.99);

    expect($product)
        ->toBeInstanceOf(Product::class)
        ->and($product->name)->toBe('Laptop')
        ->and($product->quantity_in_stock)->toBe(5)
        ->and($product->price)->toBe(999.99);
    $this->assertModelExists($product);
});

it('updates a product name', function () {
    $product = Product::factory()->create(['name' => 'Old name']);

    $updated = ProductService::updateName($product, 'New name');

    expect($updated->name)->toBe('New name');
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'New name',
    ]);
});

it('updates a product price', function () {
    $product = Product::factory()->create(['price' => 10.00]);

    $updated = ProductService::updatePrice($product, 12.50);

    expect($updated->price)->toBe(12.5);
    $this->assertModelExists($updated);
});

it('increases product stock', function () {
    $product = Product::factory()->create(['quantity_in_stock' => 4]);

    $result = ProductService::increaseStock($product, 3);

    expect($result)->toBeTrue()
        ->and($product->fresh()->quantity_in_stock)->toBe(7);
});

it('decreases product stock', function () {
    $product = Product::factory()->create(['quantity_in_stock' => 9]);

    $result = ProductService::decreaseStock($product, 4);

    expect($result)->toBeTrue()
        ->and($product->fresh()->quantity_in_stock)->toBe(5);
});
