<?php

namespace App\Actions\Validations;

use App\Actions\Data\OrderData;
use App\Actions\Validations\Contracts\Handler;
use Override;

abstract class AbstractHandler implements Handler
{
    private ?Handler $nextHandler = null;

    #[Override]
    public function setNext(Handler $handler): Handler
    {
        $this->nextHandler = $handler;

        return $handler;
    }

    public function handle(OrderData $data): mixed
    {
        if ($this->nextHandler) {
            return $this->nextHandler->handle($data);
        }

        return null;
    }
}
