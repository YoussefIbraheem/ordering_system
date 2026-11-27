<?php

use App\Actions\Commands\CommandInvoker;
use App\Actions\Commands\Commands\ApplyDiscountCommand;
use App\Actions\Commands\Commands\CancelOrderCommand;
use App\Actions\Commands\Commands\CompleteOrderCommand;
use App\Actions\Commands\Commands\PlaceOrderCommand;
use App\Actions\Commands\Contracts\CommandInterface;
use App\Actions\Data\OrderData;
use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Services\DiscountService;
use App\Services\OrderService;
use RuntimeException;

it('creates an order with its items when the place command is invoked', function () {
    $product = Product::factory()->create(['price' => 25.00]);

    $order = (new CommandInvoker)->execute(new PlaceOrderCommand(
        new OrderService,
        new OrderData(null, null, collect([$product])),
    ));

    expect($order)
        ->toBeInstanceOf(Order::class)
        ->and($order->subtotal)->toBe(25.0)
        ->and($order->total)->toBe(25.0)
        ->and($order->status)->toBe(OrderStatus::PENDING);
    $this->assertModelExists($order);
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);
});

it('undoes the most recently executed command and removes its history entry', function () {
    $product = Product::factory()->create();
    $invoker = new CommandInvoker;
    $order = $invoker->execute(new PlaceOrderCommand(
        new OrderService,
        new OrderData(null, null, collect([$product])),
    ));

    $result = $invoker->undo();

    expect($result)->toBeTrue()
        ->and($invoker->getHistory())->toBe([]);
    $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
});

it('does not add a command to history when execution throws', function () {
    $command = new class implements CommandInterface
    {
        public function execute(): mixed
        {
            throw new RuntimeException('Command failed');
        }

        public function undo(): mixed
        {
            return true;
        }
    };
    $invoker = new CommandInvoker;

    expect(fn () => $invoker->execute($command))
        ->toThrow(RuntimeException::class, 'Command failed');

    expect($invoker->getHistory())->toBe([]);
});

it('does not add a command to history when execution returns false', function () {
    $command = new class implements CommandInterface
    {
        public function execute(): mixed
        {
            return false;
        }

        public function undo(): mixed
        {
            return true;
        }
    };
    $invoker = new CommandInvoker;

    expect($invoker->execute($command))->toBeFalse()
        ->and($invoker->getHistory())->toBe([]);
});

it('completes an order when the complete command is invoked', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
    $invoker = new CommandInvoker;

    $result = $invoker->execute(
        new CompleteOrderCommand(new OrderService, $order)
    );

    expect($result)->toBeTrue()
        ->and($order->fresh()->status)->toBe(OrderStatus::COMPLETED);

    $invoker->undo();

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('cancels an order when the cancel command is invoked', function () {
    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
    $invoker = new CommandInvoker;

    $result = $invoker->execute(
        new CancelOrderCommand(new OrderService, $order)
    );

    expect($result)->toBeTrue()
        ->and($order->fresh()->status)->toBe(OrderStatus::CANCELLED);

    $invoker->undo();

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('applies a discount when the discount command is invoked', function () {
    $order = Order::factory()->create(['subtotal' => 200.00, 'total' => 200.00]);
    $discount = Discount::factory()->create([
        'type' => DiscountType::PERCENTAGE,
        'value' => 25.00,
    ]);
    $invoker = new CommandInvoker;

    $result = $invoker->execute(
        new ApplyDiscountCommand(new DiscountService, $order, $discount)
    );

    expect($result)->toBeTrue()
        ->and($order->fresh()->total)->toBe(150);

    $invoker->undo();

    expect($order->fresh()->total)->toBe(200);
});
