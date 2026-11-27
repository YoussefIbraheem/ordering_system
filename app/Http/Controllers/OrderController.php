<?php

namespace App\Http\Controllers;

use App\Actions\Commands\CommandInvoker;
use App\Actions\Commands\Commands\CancelOrderCommand;
use App\Actions\Commands\Commands\CompleteOrderCommand;
use App\Actions\Data\OrderData;
use App\Actions\OrderProcessor;
use App\Enums\OrderStatus;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class OrderController extends Controller
{
    public function store(Request $request, OrderProcessor $processor): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'discount_id' => ['nullable', 'integer', 'exists:discounts,id'],
        ]);

        $quantity = collect($validated['items'])->sum('quantity');

        if ($quantity > 50) {
            throw ValidationException::withMessages([
                'items' => 'An order cannot contain more than 50 items.',
            ]);
        }

        $products = Product::query()
            ->whereKey(collect($validated['items'])->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $items = new Collection;

        foreach ($validated['items'] as $item) {
            $product = $products->get($item['product_id']);

            if (! $product instanceof Product) {
                throw ValidationException::withMessages([
                    'items' => 'One or more products are no longer available.',
                ]);
            }

            for ($quantity = 0; $quantity < $item['quantity']; $quantity++) {
                $items->push($product);
            }
        }

        $discount = isset($validated['discount_id'])
            ? Discount::query()->findOrFail($validated['discount_id'])
            : null;

        $order = $processor->process(new OrderData(
            $this->authenticatedUser($request),
            $discount,
            $items,
        ));

        return response()->json([
            'data' => $order->load('orderItems.product'),
        ], 201);
    }

    public function pay(Request $request, string $order, CommandInvoker $commandInvoker): JsonResponse
    {
        $authenticatedUser = $this->authenticatedUser($request);

        $paidOrder = DB::transaction(function () use ($authenticatedUser, $order, $commandInvoker): Order {
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($authenticatedUser->getKey());
            $order = $this->findOwnedOrderForUpdate($user, $order);

            if ($order->status !== OrderStatus::PENDING) {
                abort(409, 'Only pending orders can be paid.');
            }

            $balanceInCents = (int) round((float) $user->balance * 100);
            $totalInCents = (int) round((float) $order->total * 100);

            if ($balanceInCents < $totalInCents) {
                abort(422, 'Insufficient balance.');
            }

            $user->balance = ($balanceInCents - $totalInCents) / 100;
            $user->save();

            if (! $commandInvoker->execute(new CompleteOrderCommand(new OrderService, $order))) {
                throw new RuntimeException('Unable to complete the paid order.');
            }

            return $order->load('orderItems.product');
        });

        return response()->json(['data' => $paidOrder]);
    }

    public function cancel(Request $request, string $order, CommandInvoker $commandInvoker): JsonResponse
    {
        $authenticatedUser = $this->authenticatedUser($request);

        $cancelledOrder = DB::transaction(function () use ($authenticatedUser, $order, $commandInvoker): Order {
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($authenticatedUser->getKey());
            $order = $this->findOwnedOrderForUpdate($user, $order);

            if ($order->status !== OrderStatus::PENDING) {
                abort(409, 'Only pending orders can be cancelled.');
            }

            if (! $commandInvoker->execute(new CancelOrderCommand(new OrderService, $order))) {
                throw new RuntimeException('Unable to cancel the order.');
            }

            return $order->load('orderItems.product');
        });

        return response()->json(['data' => $cancelledOrder]);
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function findOwnedOrderForUpdate(User $user, string $orderId): Order
    {
        return Order::query()
            ->where('user_id', $user->getKey())
            ->whereKey($orderId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
