<?php

declare(strict_types=1);

namespace Support\Events\Log\Deliveries\Policy;

use Illuminate\Contracts\Auth\Authenticatable;
use Support\Events\Log\Deliveries\Delivery;

final class Policy
{
    public function view(Authenticatable $user, Delivery $delivery): bool
    {
        return true;
    }
}
