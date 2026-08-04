<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata;

/**
 * @api
 */
final readonly class AttributeCommandRouter implements CommandRouter
{
    public function route(string $commandClass): ?string
    {
        return array_first(new \ReflectionClass($commandClass)->getAttributes(Destination::class))
            ?->newInstance()
            ?->endpoint;
    }
}
