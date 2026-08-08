<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Routing;

/**
 * Determines the endpoint a command should be sent to.
 *
 * @api
 */
interface CommandRouter
{
    /**
     * @param class-string $commandClass
     * @return non-empty-string|null destination endpoint name, or null when this router has no route for the command
     */
    public function destinationFor(string $commandClass): ?string;
}
