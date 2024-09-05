<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Authorization;

use Telephantast\MessageBus\Stamp;

/**
 * @api
 * @psalm-immutable
 */
final class Authentication implements Stamp
{
    public function __construct(
        public readonly object $passport,
    ) {}
}
