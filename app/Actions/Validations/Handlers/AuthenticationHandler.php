<?php

namespace App\Actions\Validations\Handlers;

use App\Actions\Data\OrderData;
use App\Actions\Validations\AbstractHandler;
use Exception;
use Override;

class AuthenticationHandler extends AbstractHandler
{
    #[Override]
    public function handle(OrderData $data): mixed
    {
        if (! $data->user?->exists) {
            throw new Exception('No Logged in user!');
        }

        return parent::handle($data);
    }
}
