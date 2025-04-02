<?php

declare(strict_types=1);

namespace Thesis\MessageBus\CreatedAt;

use Thesis\MessageBus\Stamp;

/**
 * @api
 */
final readonly class CreatedAt implements Stamp
{
    public function __construct(
        public \DateTimeImmutable $time = new \DateTimeImmutable(),
    ) {}
}
