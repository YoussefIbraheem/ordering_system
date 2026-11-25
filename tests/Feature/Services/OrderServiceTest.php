<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;

it('creates an order and its order items from products', function () {
    $first = Product::factory()->create(['price' => 10.00]);
    $second = Product::factory()->create(['price' => 20.00]);

    $order = OrderService::create([$first, $second]);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->subtotal)->toBe(30.0)
        ->and($order->total)->toBe(0)
        ->and($order->status)->toBe(OrderStatus::PENDING)
        ->and($order->orderItems()->count())->toBe(2);
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $first->id,
    ]);
});

it('creates an empty order without order items', function () {
    $order = OrderService::create([]);

    expect($order->subtotal)->toBe(0)
        ->and($order->orderItems()->count())->toBe(0);
});

it('completes a pending order', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    $result = OrderService::complete($order);

    expect($result)->toBeTrue()
        ->and($order->fresh()->status)->toBe(OrderStatus::COMPLETED);
});

it('cancels an order', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    $result = OrderService::cancel($order);

    expect($result)->toBeTrue()
        ->and($order->fresh()->status)->toBe(OrderStatus::CANCELLED);
});
