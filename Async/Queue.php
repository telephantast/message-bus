<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\ContextAttribute;

/**
 * @api
 */
final class Queue implements ContextAttribute
{
    /**
     * @param non-empty-string $queue
     */
    public function __construct(
        public readonly string $queue,
    ) {}
}
