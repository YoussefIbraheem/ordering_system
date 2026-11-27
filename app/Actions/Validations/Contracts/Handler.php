<?php

namespace App\Actions\Validations\Contracts;

use App\Actions\Data\OrderData;

interface Handler
{
    public function setNext(Handler $handler): Handler;

    public function handle(OrderData $data): mixed;
}
