<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\Mapping;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
final readonly class Async
{
    /**
     * @param non-empty-string $queue
     */
    public function __construct(
        public string $queue,
    ) {}
}
