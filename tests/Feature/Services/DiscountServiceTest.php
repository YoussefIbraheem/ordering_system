<?php

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Order;
use App\Services\DiscountService;

it('creates a fixed discount', function () {
    $discount = DiscountService::create('Five off', DiscountType::FIXED, 5.00);

    expect($discount)
        ->toBeInstanceOf(Discount::class)
        ->and($discount->name)->toBe('Five off')
        ->and($discount->type)->toBe(DiscountType::FIXED)
        ->and($discount->value)->toBe(5.0);
    $this->assertModelExists($discount);
});

it('rejects discounts with a non-positive value', function (float $value) {
    DiscountService::create('Invalid', DiscountType::FIXED, $value);
})->with([
    'zero' => [0.0],
    'negative' => [-1.0],
])->throws(Exception::class, 'Invalid Amount');

it('rejects percentage discounts over 100 percent', function () {
    DiscountService::create('Too much', DiscountType::PERCENTAGE, 101.0);
})->throws(Exception::class, 'Percentage discounts cannot exceed 100');

it('applies a percentage discount to an order subtotal', function () {
    $order = Order::factory()->create(['subtotal' => 200.00, 'total' => 200.00]);
    $discount = Discount::factory()->create([
        'type' => DiscountType::PERCENTAGE,
        'value' => 25.00,
    ]);

    $result = DiscountService::apply($order, $discount);

    expect($result)->toBeTrue()
        ->and($order->fresh()->total)->toBe(150);
});

it('applies a fixed discount to an order subtotal', function () {
    $order = Order::factory()->create(['subtotal' => 80.00, 'total' => 80.00]);
    $discount = Discount::factory()->create([
        'type' => DiscountType::FIXED,
        'value' => 15.00,
    ]);

    $result = DiscountService::apply($order, $discount);

    expect($result)->toBeTrue()
        ->and($order->fresh()->total)->toBe(65);
});
