<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;
use RuntimeException;

it('creates an order and its order items from products', function () {
    $first = Product::factory()->create(['price' => 10.00]);
    $second = Product::factory()->create(['price' => 20.00]);

    $order = OrderService::create([$first, $second]);

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->subtotal)->toBe(30.0)
        ->and($order->total)->toBe(30.0)
        ->and($order->status)->toBe(OrderStatus::PENDING)
        ->and($order->orderItems()->count())->toBe(2);
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $first->id,
    ]);
});

it('stores repeated products as one item with the requested quantity', function () {
    $product = Product::factory()->create(['price' => 12.50]);

    $order = OrderService::create([$product, $product]);

    expect($order->subtotal)->toBe(25.0)
        ->and($order->total)->toBe(25.0)
        ->and($order->orderItems()->count())->toBe(1)
        ->and($order->orderItems()->first()->quantity)->toBe(2)
        ->and($order->orderItems()->first()->price)->toBe(12.5);
});

it('rolls back the order when saving an item fails', function () {
    $product = Product::factory()->create();
    $event = 'eloquent.creating: '.OrderItem::class;
    Event::listen($event, fn () => throw new RuntimeException('Item persistence failed'));

    try {
        expect(fn () => OrderService::create([$product]))
            ->toThrow(RuntimeException::class, 'Item persistence failed');
    } finally {
        Event::forget($event);
    }

    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseCount('order_items', 0);
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
