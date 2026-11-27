<?php

use App\Actions\Commands\CommandInvoker;
use App\Actions\Commands\Commands\CancelOrderCommand;
use App\Actions\Commands\Commands\PlaceOrderCommand;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

it('requires authentication to place an order', function () {
    $this->postJson('/api/orders', [
        'items' => [],
    ])->assertUnauthorized();
});

it('places an order through the order processor', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'price' => 12.5,
        'quantity_in_stock' => 3,
    ]);
    $invoker = new CommandInvoker;
    $this->app->instance(CommandInvoker::class, $invoker);

    $response = $this->withToken($user->createToken('test')->plainTextToken)
        ->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.status', OrderStatus::PENDING->value)
        ->assertJsonPath('data.order_items.0.quantity', 2);

    $this->assertDatabaseHas('orders', [
        'id' => $response->json('data.id'),
        'user_id' => $user->id,
        'subtotal' => 2500,
        'status' => OrderStatus::PENDING->value,
    ]);
    expect($invoker->getHistory())->toHaveCount(1)
        ->and($invoker->getHistory()[0])->toBeInstanceOf(PlaceOrderCommand::class);
});

it('rejects an order when a requested quantity exceeds stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['quantity_in_stock' => 1]);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])
        ->assertUnprocessable()
        ->assertInvalid(['items' => 'Insufficient stock']);

    $this->assertDatabaseCount('orders', 0);
});

it('rejects malformed order input', function () {
    $user = User::factory()->create();

    $this->withToken($user->createToken('test')->plainTextToken)
        ->postJson('/api/orders', ['items' => []])
        ->assertUnprocessable()
        ->assertInvalid(['items']);

    $this->assertDatabaseCount('orders', 0);
});

it('charges the authenticated user and completes a pending order', function () {
    $user = User::factory()->create(['balance' => 40]);
    $order = Order::factory()->for($user)->create([
        'total' => 25,
        'status' => OrderStatus::PENDING,
    ]);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->postJson("/api/orders/{$order->id}/pay")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::COMPLETED->value);

    expect($user->fresh()->balance)->toEqual(15)
        ->and($order->fresh()->status)->toBe(OrderStatus::COMPLETED);
});

it('does not charge a user with insufficient balance', function () {
    $user = User::factory()->create(['balance' => 10]);
    $order = Order::factory()->for($user)->create([
        'total' => 25,
        'status' => OrderStatus::PENDING,
    ]);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->postJson("/api/orders/{$order->id}/pay")
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Insufficient balance.');

    expect($user->fresh()->balance)->toEqual(10)
        ->and($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('does not pay an order that is not pending', function () {
    $user = User::factory()->create(['balance' => 100]);
    $order = Order::factory()->for($user)->create([
        'total' => 25,
        'status' => OrderStatus::CANCELLED,
    ]);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->postJson("/api/orders/{$order->id}/pay")
        ->assertConflict();

    expect($user->fresh()->balance)->toEqual(100)
        ->and($order->fresh()->status)->toBe(OrderStatus::CANCELLED);
});

it('does not allow a user to pay another user order', function () {
    $user = User::factory()->create(['balance' => 100]);
    $owner = User::factory()->create();
    $order = Order::factory()->for($owner)->create();

    $this->withToken($user->createToken('test')->plainTextToken)
        ->postJson("/api/orders/{$order->id}/pay")
        ->assertNotFound();

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING)
        ->and($user->fresh()->balance)->toEqual(100);
});

it('cancels a pending order through a reversible command', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::PENDING,
    ]);
    $invoker = new CommandInvoker;
    $this->app->instance(CommandInvoker::class, $invoker);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->postJson("/api/orders/{$order->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::CANCELLED->value);

    expect($invoker->getHistory())->toHaveCount(1)
        ->and($invoker->getHistory()[0])->toBeInstanceOf(CancelOrderCommand::class)
        ->and($order->fresh()->status)->toBe(OrderStatus::CANCELLED);

    expect($invoker->undo())->toBeTrue()
        ->and($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('does not allow a user to cancel another user order', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $order = Order::factory()->for($owner)->create();

    $this->withToken($user->createToken('test')->plainTextToken)
        ->postJson("/api/orders/{$order->id}/cancel")
        ->assertNotFound();

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('does not cancel an order that is not pending', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::COMPLETED,
    ]);

    $this->withToken($user->createToken('test')->plainTextToken)
        ->postJson("/api/orders/{$order->id}/cancel")
        ->assertConflict();

    expect($order->fresh()->status)->toBe(OrderStatus::COMPLETED);
});

it('validates order quantities', function () {
    $user = User::factory()->create();

    $this->withToken($user->createToken('test')->plainTextToken)
        ->postJson('/api/orders', [
            'items' => [
                ['product_id' => 1, 'quantity' => 0],
            ],
        ])
        ->assertUnprocessable()
        ->assertInvalid(['items.0.product_id', 'items.0.quantity']);

    $this->assertDatabaseCount('orders', 0);
});
