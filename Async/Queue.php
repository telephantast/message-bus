<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\ContextAttribute;

/**
 * @api
 */
final readonly class Queue implements ContextAttribute
{
    /**
     * @param non-empty-string $queue
     */
    public function __construct(
        public string $queue,
    ) {}
}
