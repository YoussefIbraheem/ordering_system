<?php

use App\Actions\Commands\CommandInvoker;
use App\Actions\Commands\Commands\PlaceOrderCommand;
use App\Actions\Data\OrderData;
use App\Actions\OrderProcessor;
use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Exception;

it('validates the full chain before creating an order with discounted duplicate items', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'price' => 10,
        'quantity_in_stock' => 2,
    ]);
    $discount = Discount::factory()->create([
        'type' => DiscountType::FIXED,
        'value' => 5,
    ]);
    $invoker = new CommandInvoker;
    $processor = new OrderProcessor($invoker, new OrderService);

    $order = $processor->process(new OrderData(
        $user,
        $discount,
        collect([$product, $product]),
    ));

    expect($order->subtotal)->toBe(20)
        ->and($order->total)->toBe(15)
        ->and($order->user_id)->toBe($user->id)
        ->and($order->discount_id)->toBe($discount->id)
        ->and($invoker->getHistory())->toHaveCount(1)
        ->and($invoker->getHistory()[0])->toBeInstanceOf(PlaceOrderCommand::class);
    $this->assertModelExists($order);
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 1000,
    ]);
});

it('creates an order when no discount is provided', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'price' => 8,
        'quantity_in_stock' => 1,
    ]);
    $processor = new OrderProcessor(new CommandInvoker, new OrderService);

    $order = $processor->process(new OrderData(
        $user,
        null,
        collect([$product]),
    ));

    expect($order->subtotal)->toBe(8)
        ->and($order->total)->toBe(8)
        ->and($order->discount_id)->toBeNull();
    $this->assertModelExists($order);
});

it('rejects orders without a persisted user before creating records', function () {
    $product = Product::factory()->create(['quantity_in_stock' => 1]);
    $processor = new OrderProcessor(new CommandInvoker, new OrderService);

    expect(fn () => $processor->process(new OrderData(
        null,
        null,
        collect([$product]),
    )))->toThrow(Exception::class, 'No Logged in user!');

    $this->assertDatabaseCount('orders', 0);
});

it('rejects duplicate items when their combined quantity exceeds stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['quantity_in_stock' => 1]);
    $processor = new OrderProcessor(new CommandInvoker, new OrderService);

    expect(fn () => $processor->process(new OrderData(
        $user,
        null,
        collect([$product, $product]),
    )))->toThrow(Exception::class, 'Insufficient stock');

    $this->assertDatabaseCount('orders', 0);
});

it('rejects discounts that exceed the order subtotal', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'price' => 10,
        'quantity_in_stock' => 1,
    ]);
    $discount = Discount::factory()->create([
        'type' => DiscountType::FIXED,
        'value' => 11,
    ]);
    $processor = new OrderProcessor(new CommandInvoker, new OrderService);

    expect(fn () => $processor->process(new OrderData(
        $user,
        $discount,
        collect([$product]),
    )))->toThrow(Exception::class, 'Discount cannot exceed the order subtotal');

    $this->assertDatabaseCount('orders', 0);
});

it('rejects percentage discounts greater than one hundred', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'price' => 10,
        'quantity_in_stock' => 1,
    ]);
    $discount = Discount::factory()->create([
        'type' => DiscountType::PERCENTAGE,
        'value' => 101,
    ]);
    $processor = new OrderProcessor(new CommandInvoker, new OrderService);

    expect(fn () => $processor->process(new OrderData(
        $user,
        $discount,
        collect([$product]),
    )))->toThrow(Exception::class, 'Percentage discounts cannot exceed 100');

    $this->assertDatabaseCount('orders', 0);
});

it('rejects orders above the fraud item limit', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'price' => 1,
        'quantity_in_stock' => 51,
    ]);
    $processor = new OrderProcessor(new CommandInvoker, new OrderService);

    expect(fn () => $processor->process(new OrderData(
        $user,
        null,
        collect(array_fill(0, 51, $product)),
    )))->toThrow(Exception::class, 'Order limit exceeded');

    $this->assertDatabaseCount('orders', 0);
});
