<?php

declare(strict_types=1);

namespace Thesis\MessageBus\CommandRouter;

use Thesis\MessageBus\CommandRouter;

/**
 * @api
 */
final readonly class Chain implements CommandRouter
{
    /**
     * @param iterable<CommandRouter> $routers
     */
    public function __construct(
        private iterable $routers,
    ) {}

    public function routeCommand(string $commandClass): ?string
    {
        foreach ($this->routers as $router) {
            $endpoint = $router->routeCommand($commandClass);

            if ($endpoint !== null) {
                return $endpoint;
            }
        }

        return null;
    }
}
