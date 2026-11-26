<?php

namespace App\Actions\Commands\Commands;

use App\Actions\Commands\Contracts\CommandInterface;
use App\Models\Discount;
use App\Models\Order;
use App\Services\DiscountService;
use Override;

class ApplyDiscountCommand implements CommandInterface
{
    public function __construct(
        private DiscountService $service,
        private Order $order,
        private Discount $discount
    ) {}

    #[Override]
    public function execute()
    {
        return $this->service->apply($this->order, $this->discount);
    }
}
