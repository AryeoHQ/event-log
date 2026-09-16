<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Mqtt;

use Support\Events\Log\Logs\Data\Version\Contracts\Version;

enum PayloadVersion: string implements Version
{
    case V1 = 'v1';

    case V2 = 'v2';
}
