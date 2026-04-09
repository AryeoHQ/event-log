<?php

declare(strict_types=1);

namespace Support\Events\Log\Logs\Policy;

use Illuminate\Contracts\Auth\Authenticatable;
use Support\Events\Log\Logs\Entities\Log;

final class Policy
{
    public function view(Authenticatable $user, Log $log): bool
    {
        return true;
    }
}
