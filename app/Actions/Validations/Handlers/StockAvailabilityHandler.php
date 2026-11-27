<?php

namespace App\Actions\Validations\Handlers;

use App\Actions\Data\OrderData;
use App\Actions\Validations\AbstractHandler;
use App\Models\Product;
use Illuminate\Validation\ValidationException;
use Override;

class StockAvailabilityHandler extends AbstractHandler
{
    #[Override]
    public function handle(OrderData $data): mixed
    {
        $items = $data->items;

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'No items in the order.',
            ]);
        }

        $requestedQuantities = [];

        foreach ($items as $item) {
            if (! $item instanceof Product || ! $item->exists) {
                throw ValidationException::withMessages([
                    'items' => 'Order items must be existing products.',
                ]);
            }

            $requestedQuantities[$item->getKey()] = ($requestedQuantities[$item->getKey()] ?? 0) + 1;
        }

        $products = Product::query()
            ->whereKey(array_keys($requestedQuantities))
            ->get()
            ->keyBy(fn (Product $product): int => (int) $product->getKey());

        foreach ($requestedQuantities as $productId => $quantity) {
            $product = $products->get((int) $productId);

            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => 'An ordered product no longer exists.',
                ]);
            }

            if ($product->quantity_in_stock < $quantity) {
                throw ValidationException::withMessages([
                    'items' => 'Insufficient stock for '.$product->name.'.',
                ]);
            }
        }

        return parent::handle($data);
    }
}
