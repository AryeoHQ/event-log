<?php

declare(strict_types=1);

namespace Support\Events\Log\IdentifiesLoggable;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class IdentifiesLoggable {}
