<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Order;
use Exception;

class DiscountService
{
    public static function create(string $name, DiscountType $type, float $value)
    {
        if ($value <= 0) {
            throw new Exception('Invalid Amount');
        }

        if ($type == DiscountType::PERCENTAGE && $value > 100) {
            throw new Exception('Percentage discounts cannot exceed 100');
        }

        $discount = Discount::create([
            'name' => $name,
            'type' => $type,
            'value' => $value,
        ]);

        return $discount;
    }

    public static function apply(Order $order, Discount $discount)
    {

        $deducatable_value = 0;

        if ($discount->type == DiscountType::PERCENTAGE) {
            $deducatable_value = $order->subtotal * ($discount->value / 100);
        }

        if ($discount->type == DiscountType::FIXED) {
            $deducatable_value = $discount->value;
        }

        $total = $order->subtotal - $deducatable_value;

        return (bool) $order->update([
            'total' => $total,
        ]);
    }
}
