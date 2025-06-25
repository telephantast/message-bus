<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

final readonly class Endpoint
{
    /**
     * @param non-empty-string $endpoint
     */
    public function __construct(
        public string $endpoint,
    ) {}
}
