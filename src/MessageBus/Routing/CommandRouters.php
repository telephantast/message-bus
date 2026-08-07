<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Routing;

/**
 * @api
 */
final readonly class CommandRouters implements CommandRouter
{
    /**
     * @param list<CommandRouter> $routers
     */
    public function __construct(
        private array $routers,
    ) {}

    public function route(string $commandClass): ?string
    {
        foreach ($this->routers as $router) {
            $destination = $router->route($commandClass);

            if ($destination !== null) {
                return $destination;
            }
        }

        return null;
    }
}
