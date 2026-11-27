<?php

namespace App\Actions\Validations\Handlers;

use App\Actions\Data\OrderData;
use App\Actions\Validations\AbstractHandler;
use App\Enums\DiscountType;
use App\Models\Product;
use Illuminate\Validation\ValidationException;
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
            throw ValidationException::withMessages([
                'discount_id' => 'Invalid discount.',
            ]);
        }

        if ($discount->value <= 0) {
            throw ValidationException::withMessages([
                'discount_id' => 'Invalid discount amount.',
            ]);
        }

        $subtotal = $data->items->sum(fn (Product $product) => $product->price);

        if ($discount->type === DiscountType::PERCENTAGE && $discount->value > 100) {
            throw ValidationException::withMessages([
                'discount_id' => 'Percentage discounts cannot exceed 100.',
            ]);
        }

        if ($discount->type === DiscountType::FIXED && $discount->value > $subtotal) {
            throw ValidationException::withMessages([
                'discount_id' => 'Discount cannot exceed the order subtotal.',
            ]);
        }

        return parent::handle($data);
    }
}
