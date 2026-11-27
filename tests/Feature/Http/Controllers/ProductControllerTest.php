<?php

use App\Models\Product;

it('shows products without requiring authentication', function () {
    Product::factory()->create([
        'name' => 'Coffee',
        'price' => 12.5,
        'quantity_in_stock' => 4,
    ]);

    $this->getJson('/api/products')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Coffee')
        ->assertJsonPath('data.0.price', 12.5)
        ->assertJsonPath('data.0.quantity_in_stock', 4);
});
