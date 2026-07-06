<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Command
{
    /**
     * @param ?non-empty-string $destination Endpoint name
     */
    public function __construct(
        public ?string $destination = null,
    ) {}
}
