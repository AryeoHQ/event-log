<?php

declare(strict_types=1);

namespace Support\Events\Log\Destinations\Policy;

use Illuminate\Contracts\Auth\Authenticatable;
use Support\Events\Log\Destinations\Destination;

final class Policy
{
    public function view(Authenticatable $user, Destination $destination): bool
    {
        return true;
    }
}
