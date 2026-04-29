<?php

declare(strict_types=1);

namespace Thesis\MessageBus\CommandRouter;

use Thesis\MessageBus\CommandRouter;

/**
 * @api
 */
final class Cached implements CommandRouter
{
    /**
     * @var array<class-string, ?non-empty-string>
     */
    private array $cache = [];

    public function __construct(
        private readonly CommandRouter $router,
    ) {}

    public function routeCommand(string $commandClass): ?string
    {
        if (\array_key_exists($commandClass, $this->cache)) {
            return $this->cache[$commandClass];
        }

        return $this->cache[$commandClass] = $this->router->routeCommand($commandClass);
    }
}
