<?php

namespace App\Actions\Data;

use App\Actions\Data\Contracts\ActionData;
use App\Models\Discount;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class OrderData implements ActionData
{
    /**
     * @param  Collection<int, Product>  $items
     */
    public function __construct(
        public readonly ?User $user,
        public readonly ?Discount $discount,
        public readonly Collection $items,
    ) {}
}
