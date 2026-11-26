<?php

namespace App\Actions\Commands;

use App\Actions\Commands\Contracts\CommandInterface;

class CommandInvoker
{
    public function execute(CommandInterface $command)
    {
        return $command->execute();
    }
}
