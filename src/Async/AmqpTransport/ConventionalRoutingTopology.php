<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\AmqpTransport;

/**
 * @api
 */
final readonly class ConventionalRoutingTopology implements RoutingTopology
{
    public function resolveExchange(string $messageClass): string
    {
        /** @psalm-var non-empty-string */
        return str_replace('\\', '.', $messageClass);
    }
}
