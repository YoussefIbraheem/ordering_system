<?php

namespace App\Actions\Validations\Handlers;

use App\Actions\Data\OrderData;
use App\Actions\Validations\AbstractHandler;
use App\Enums\DiscountType;
use App\Models\Product;
use Exception;
use Override;

class DiscountValidationHandler extends AbstractHandler
{
    #[Override]
    public function handle(OrderData $data): mixed
    {
        $discount = $data->discount;

        if ($discount === null) {
            return parent::handle($data);
        }

        if (! $discount->exists) {
            throw new Exception('Invalid discount');
        }

        if ($discount->value <= 0) {
            throw new Exception('Invalid discount amount');
        }

        $subtotal = $data->items->sum(fn (Product $product) => $product->price);

        if ($discount->type === DiscountType::PERCENTAGE && $discount->value > 100) {
            throw new Exception('Percentage discounts cannot exceed 100');
        }

        if ($discount->type === DiscountType::FIXED && $discount->value > $subtotal) {
            throw new Exception('Discount cannot exceed the order subtotal');
        }

        return parent::handle($data);
    }
}
