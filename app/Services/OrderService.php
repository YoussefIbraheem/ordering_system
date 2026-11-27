<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class OrderService
{
    /**
     * @param  array<int, Product>  $products
     */
    public static function create(array $products, ?User $user = null, ?Discount $discount = null): Order
    {
        $productsCollection = collect($products);

        foreach ($productsCollection as $product) {
            if (! $product instanceof Product || ! $product->exists) {
                throw new InvalidArgumentException('Orders can only contain existing products');
            }
        }

        return DB::transaction(function () use ($productsCollection, $user, $discount): Order {
            $subtotal = $productsCollection->sum(fn (Product $product) => $product->price);
            $order = Order::create([
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'status' => OrderStatus::PENDING,
                'user_id' => $user?->getKey(),
                'discount_id' => $discount?->getKey(),
            ]);

            $groupedProducts = $productsCollection->groupBy(fn (Product $product) => $product->getKey());

            foreach ( $groupedProducts as $productId => $matchingProducts) {
                $product = $matchingProducts->first();

                $order->orderItems()->create([
                    'product_id' => $productId,
                    'price' => $product->price,
                    'quantity' => $matchingProducts->count(),
                ]);
            }

            if ($discount !== null) {
                DiscountService::apply($order, $discount);
            }

            return $order;
        });
    }

    public static function delete(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $order->orderItems()->delete();

            if (! $order->delete()) {
                throw new RuntimeException('Unable to delete the order while undoing its creation');
            }

            return true;
        });
    }

    public static function complete(Order $order): bool
    {
        return (bool) $order->update([
            'status' => OrderStatus::COMPLETED,
        ]);
    }

    public static function cancel(Order $order): bool
    {
        return (bool) $order->update([
            'status' => OrderStatus::CANCELLED,
        ]);
    }
}
