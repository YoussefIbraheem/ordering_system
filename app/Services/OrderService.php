<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;

class OrderService
{
    public static function create(array $products)
    {
        $productsCollection = collect($products);
        $subtotal = $productsCollection->sum('price');
        $order = Order::create([
            'subtotal' => $subtotal,
            'total' => 0,
            'status' => OrderStatus::PENDING,
        ]);

        $items = [];

        foreach ($products as $product) {
            $item = [
                'product_id' => $product->id,
                'order_id' => $order->id,
                'price' => $product->getRawOriginal('price'),
            ];

            $items[] = $item;
        }

        if ($items !== []) {
            OrderItem::query()->insert($items);
        }

        return $order;
    }

    public static function complete(Order $order)
    {
        $order->update([
            'status' => OrderStatus::COMPLETED,
        ]);

        return true;
    }

    public static function cancel(Order $order)
    {
        $order->update([
            'status' => OrderStatus::CANCELLED,
        ]);

        return true;
    }
}
