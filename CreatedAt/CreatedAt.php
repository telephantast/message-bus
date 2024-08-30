<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\CreatedAt;

use Telephantast\MessageBus\Stamp;

/**
 * @api
 * @psalm-immutable
 */
final class CreatedAt implements Stamp
{
    public function __construct(
        public readonly \DateTimeImmutable $time = new \DateTimeImmutable(),
    ) {}
}
