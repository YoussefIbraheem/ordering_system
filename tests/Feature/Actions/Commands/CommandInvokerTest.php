<?php

use App\Actions\Commands\CommandInvoker;
use App\Actions\Commands\Commands\ApplyDiscountCommand;
use App\Actions\Commands\Commands\CancelOrderCommand;
use App\Actions\Commands\Commands\CompleteOrderCommand;
use App\Actions\Commands\Commands\PlaceOrderCommand;
use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Services\DiscountService;
use App\Services\OrderService;

it('creates an order with its items when the place command is invoked', function () {
    $product = Product::factory()->create(['price' => 25.00]);

    $order = (new CommandInvoker)->execute(
        new PlaceOrderCommand(new OrderService, [$product])
    );

    expect($order)
        ->toBeInstanceOf(Order::class)
        ->and($order->subtotal)->toBe(25.0)
        ->and($order->status)->toBe(OrderStatus::PENDING);
    $this->assertModelExists($order);
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);
});

it('completes an order when the complete command is invoked', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    $result = (new CommandInvoker)->execute(
        new CompleteOrderCommand(new OrderService, $order)
    );

    expect($result)->toBeTrue()
        ->and($order->fresh()->status)->toBe(OrderStatus::COMPLETED);
});

it('cancels an order when the cancel command is invoked', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    $result = (new CommandInvoker)->execute(
        new CancelOrderCommand(new OrderService, $order)
    );

    expect($result)->toBeTrue()
        ->and($order->fresh()->status)->toBe(OrderStatus::CANCELLED);
});

it('applies a discount when the discount command is invoked', function () {
    $order = Order::factory()->create(['subtotal' => 200.00, 'total' => 200.00]);
    $discount = Discount::factory()->create([
        'type' => DiscountType::PERCENTAGE,
        'value' => 25.00,
    ]);

    $result = (new CommandInvoker)->execute(
        new ApplyDiscountCommand(new DiscountService, $order, $discount)
    );

    expect($result)->toBeTrue()
        ->and($order->fresh()->total)->toBe(150);
});
