<?php

namespace App\Actions\Commands\Commands;

use App\Actions\Commands\Contracts\CommandInterface;
use App\Services\OrderService;
use Override;

class PlaceOrderCommand implements CommandInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private OrderService $service,
        private array $products
    ) {}

    #[Override]
    public function execute()
    {
        return $this->service->create($this->products);
    }
}
