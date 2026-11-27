<?php

namespace App\Actions\Validations\Handlers;

use App\Actions\Data\OrderData;
use App\Actions\Validations\AbstractHandler;
use App\Models\Product;
use Illuminate\Validation\ValidationException;
use Override;

class FraudCheckHandler extends AbstractHandler
{
    #[Override]
    public function handle(OrderData $data): mixed
    {
        $items = $data->items;

        if ($items->count() > 50) {
            throw ValidationException::withMessages([
                'items' => 'Order limit exceeded.',
            ]);
        }

        $orderTotal = $items->sum(fn (Product $product) => $product->price);

        if ($orderTotal > 50000) {
            throw ValidationException::withMessages([
                'items' => 'For safety purposes, the order has not been created, contact sales if you need large quotas.',
            ]);
        }

        return parent::handle($data);
    }
}
