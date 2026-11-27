<?php

namespace App\Actions\Commands\Commands;

use App\Actions\Commands\Contracts\CommandInterface;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use LogicException;
use Override;

class CompleteOrderCommand implements CommandInterface
{
    private ?OrderStatus $previousStatus = null;


    public function __construct(
        private OrderService $service,
        private Order $order
    ) {}

    #[Override]
    public function execute(): bool
    {
        $this->previousStatus = $this->order->status;

        return $this->service->complete($this->order);
    }

    #[Override]
    public function undo(): bool
    {
        if ($this->previousStatus === null) {
            throw new LogicException('Cannot undo a complete-order command that has not executed');
        }

        return (bool) $this->order->update(['status' => $this->previousStatus]);
    }
}
