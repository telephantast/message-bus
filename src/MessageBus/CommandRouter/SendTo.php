<?php

declare(strict_types=1);

namespace Thesis\MessageBus\CommandRouter;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class SendTo
{
    /**
     * @param non-empty-string $endpoint Destination endpoint name
     */
    public function __construct(
        public string $endpoint,
    ) {}
}
