<?php

declare(strict_types=1);

namespace Thesis\MessageBus\CommandRouter\Internal;

use Thesis\MessageBus\CommandRouter;
use Thesis\MessageBus\CommandRouter\SendTo;

/**
 * @internal
 */
final readonly class Attribute implements CommandRouter
{
    public function routeCommand(string $commandClass): ?string
    {
        return (new \ReflectionClass($commandClass)
            ->getAttributes(SendTo::class)[0] ?? null)
            ?->newInstance()
            ->destination;
    }
}
