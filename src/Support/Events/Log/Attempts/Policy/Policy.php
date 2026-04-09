<?php

declare(strict_types=1);

namespace Support\Events\Log\Attempts\Policy;

use Illuminate\Contracts\Auth\Authenticatable;
use Support\Events\Log\Attempts\Entities\Attempt;

final class Policy
{
    public function view(Authenticatable $user, Attempt $attempt): bool
    {
        return true;
    }
}
