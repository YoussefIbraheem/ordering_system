<?php

namespace App\Actions\Commands\Contracts;

interface CommandInterface
{
    public function execute(): mixed;

    public function undo(): mixed;
}
