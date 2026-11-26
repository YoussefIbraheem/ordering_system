<?php

namespace App\Actions\Commands\Commands;

use App\Actions\Commands\Contracts\CommandInterface;
use App\Models\Order;
use App\Services\OrderService;
use Override;

class CancelOrderCommand implements CommandInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private OrderService $service,
        private Order $order
    ) {}

    #[Override]
    public function execute()
    {
        return $this->service->cancel($this->order);
    }
}
