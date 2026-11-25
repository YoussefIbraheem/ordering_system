<?php

namespace App\Actions\Commands;

class Invoker
{
    /**
     * Create a new class instance.
     */
    public function __construct(private Receiver $receiver)
    {
        //
    }

}
