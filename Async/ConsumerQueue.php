<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\InheritableContextAttribute;

/**
 * @api
 */
final readonly class ConsumerQueue implements InheritableContextAttribute
{
    /**
     * @param non-empty-string $queue
     */
    public function __construct(
        public string $queue,
    ) {}
}
