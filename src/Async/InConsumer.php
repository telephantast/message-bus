<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use Thesis\MessageBus\InheritableContextAttribute;

/**
 * @api
 */
final readonly class InConsumer implements InheritableContextAttribute
{
    /**
     * @param non-empty-string $queue
     */
    public function __construct(
        public string $queue,
    ) {}
}
