<?php

namespace App\Actions\Commands\Commands;

use App\Actions\Commands\Contracts\CommandInterface;
use App\Models\Discount;
use App\Models\Order;
use App\Services\DiscountService;
use LogicException;
use Override;

class ApplyDiscountCommand implements CommandInterface
{
    private ?float $previousTotal = null;

    public function __construct(
        private DiscountService $service,
        private Order $order,
        private Discount $discount
    ) {}


    #[Override]
    public function execute(): bool
    {
        $this->previousTotal = (float) $this->order->total;

        return $this->service->apply($this->order, $this->discount);
    }

    #[Override]
    public function undo(): bool
    {
        if ($this->previousTotal === null) {
            throw new LogicException('Cannot undo an apply-discount command that has not executed');
        }

        return (bool) $this->order->update(['total' => $this->previousTotal]);
    }
}
