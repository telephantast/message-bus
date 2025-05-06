<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Thesis\MessageBus\Stamp;

/**
 * @api
 */
final readonly class SourceEndpoint implements Stamp
{
    /**
     * @param non-empty-string $sourceEndpoint
     */
    public function __construct(
        public string $sourceEndpoint,
    ) {}
}
