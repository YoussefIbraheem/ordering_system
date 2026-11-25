<?php

namespace App\Actions\Commands;

abstract class Command implements CommandInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected Receiver $receiver)
    {}
}
