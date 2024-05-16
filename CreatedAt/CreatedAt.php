<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\CreatedAt;

use Telephantast\MessageBus\Stamp;

/**
 * @api
 * @psalm-immutable
 */
final readonly class CreatedAt implements Stamp
{
    public function __construct(
        public \DateTimeImmutable $time = new \DateTimeImmutable(),
    ) {}
}
