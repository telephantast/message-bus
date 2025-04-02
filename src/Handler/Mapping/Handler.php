<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler\Mapping;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
final readonly class Handler
{
    /**
     * @param ?non-empty-string $id
     */
    public function __construct(
        public ?string $id = null,
    ) {}
}
