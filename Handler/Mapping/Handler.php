<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Handler\Mapping;

/**
 * @api
 */
#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
final class Handler
{
    /**
     * @param ?non-empty-string $id
     */
    public function __construct(
        public readonly ?string $id = null,
    ) {}
}
