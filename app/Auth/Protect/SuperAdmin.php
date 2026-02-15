<?php

namespace App\Auth\Protect;

use Statamic\Auth\Protect\Protectors\Authenticated;
use Statamic\Exceptions\ForbiddenHttpException;

class SuperAdmin extends Authenticated
{
    public function protect(): void
    {
        parent::protect();

        if (! auth()->user()?->super) {
            throw new ForbiddenHttpException();
        }
    }
}