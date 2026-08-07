<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Routing;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Destination
{
    /**
     * @param non-empty-string $endpoint
     */
    public function __construct(
        public string $endpoint,
    ) {}
}
