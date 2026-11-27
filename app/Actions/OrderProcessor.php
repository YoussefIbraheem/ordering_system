<?php

namespace App\Actions;

use App\Actions\Commands\CommandInvoker;
use App\Actions\Commands\Commands\PlaceOrderCommand;
use App\Actions\Data\OrderData;
use App\Actions\Validations\Handlers\AuthenticationHandler;
use App\Actions\Validations\Handlers\DiscountValidationHandler;
use App\Actions\Validations\Handlers\FraudCheckHandler;
use App\Actions\Validations\Handlers\StockAvailabilityHandler;
use App\Models\Order;
use App\Services\OrderService;

class OrderProcessor
{
    public function __construct(
        private CommandInvoker $commandInvoker,
        private OrderService $orderService,
    ) {}

    public function process(OrderData $data): Order
    {
        $authenticationHandler = new AuthenticationHandler;
        $authenticationHandler
            ->setNext(new StockAvailabilityHandler)
            ->setNext(new FraudCheckHandler)
            ->setNext(new DiscountValidationHandler);

        $authenticationHandler->handle($data);

        return $this->commandInvoker->execute(
            new PlaceOrderCommand($this->orderService, $data)
        );
    }
}
