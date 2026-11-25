<?php

namespace App\Actions\Services;

use App\Enums\DiscountType;
use App\Models\Order;

class CreateOrder
{
    public function __invoke(array $items, ?DiscountType $discountType, ?int $dicsountAmount)
    {

        $itemsCollection = collect($items);
        $totalAmount = $itemsCollection->sum("price");


        $order = Order::create();
    }
}
