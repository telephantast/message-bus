<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Command;
use Thesis\MessageBus\CommandRouter;

/**
 * @internal
 */
final readonly class AttributeCommandRouter implements CommandRouter
{
    public function routeCommand(string $commandClass): ?string
    {
        return (new \ReflectionClass($commandClass)
            ->getAttributes(Command::class)[0] ?? null)
            ?->newInstance()
            ->destination;
    }
}
