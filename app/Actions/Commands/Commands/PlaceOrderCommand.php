<?php

namespace App\Actions\Commands\Commands;

use App\Actions\Commands\Contracts\CommandInterface;
use App\Actions\Data\OrderData;
use App\Models\Order;
use App\Services\OrderService;
use LogicException;
use Override;

class PlaceOrderCommand implements CommandInterface
{
    private ?Order $order = null;

    public function __construct(
        private OrderService $service,
        private OrderData $data
    ) {}

    #[Override]
    public function execute(): Order
    {
        return $this->order = $this->service->create(
            $this->data->items->all(),
            $this->data->user,
            $this->data->discount,
        );
    }

    #[Override]
    public function undo(): bool
    {
        if (! $this->order) {
            throw new LogicException('Cannot undo a place-order command that has not executed');
        }

        return $this->service->delete($this->order);
    }
}
