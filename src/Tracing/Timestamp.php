<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Thesis\MessageBus\Stamp;

/**
 * @api
 */
final readonly class Timestamp implements Stamp
{
    public function __construct(
        public \DateTimeImmutable $timestamp = new \DateTimeImmutable(),
    ) {}
}
