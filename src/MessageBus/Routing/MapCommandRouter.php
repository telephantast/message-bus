<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Routing;

/**
 * @api
 */
final readonly class MapCommandRouter implements CommandRouter
{
    /**
     * @param array<class-string, non-empty-string> $map
     */
    public function __construct(
        private array $map,
    ) {}

    public function destinationFor(string $commandClass): ?string
    {
        return $this->map[$commandClass] ?? null;
    }
}
