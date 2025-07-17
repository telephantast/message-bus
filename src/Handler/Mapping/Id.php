<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler\Mapping;

#[\Attribute(\Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
final readonly class Id
{
    /**
     * @param non-empty-string $id
     */
    public function __construct(
        public string $id,
    ) {}
}
