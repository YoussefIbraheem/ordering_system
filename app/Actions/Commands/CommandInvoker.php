<?php

namespace App\Actions\Commands;

use App\Actions\Commands\Contracts\CommandInterface;
use LogicException;

class CommandInvoker
{
    /** @var list<CommandInterface> */
    private array $history = [];

    public function execute(CommandInterface $command): mixed
    {
        $result = $command->execute();

        if ($result !== false) {
            $this->history[] = $command;
        }

        return $result;
    }

    public function undo(): mixed
    {
        $command = end($this->history);

        if (! $command instanceof CommandInterface) {
            throw new LogicException('There are no successful commands to undo');
        }

        $result = $command->undo();

        if ($result !== false) {
            array_pop($this->history);
        }

        return $result;
    }

    /**
     * @return list<CommandInterface>
     */
    public function getHistory(): array
    {
        return $this->history;
    }
}
