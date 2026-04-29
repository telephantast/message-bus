<?php

declare(strict_types=1);

namespace Thesis\MessageBus\CommandRouter;

use Thesis\MessageBus\CommandRouter;

/**
 * @api
 */
final readonly class Map implements CommandRouter
{
    /**
     * @param array<class-string, non-empty-string> $map
     */
    public function __construct(
        private array $map,
    ) {}

    public function routeCommand(string $commandClass): ?string
    {
        return $this->map[$commandClass] ?? null;
    }
}
