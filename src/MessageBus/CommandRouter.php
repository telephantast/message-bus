<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
interface CommandRouter
{
    /**
     * @param class-string $commandClass
     * @return ?non-empty-string Destination endpoint name
     */
    public function routeCommand(string $commandClass): ?string;
}
