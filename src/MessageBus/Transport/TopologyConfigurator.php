<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

/**
 * @api
 */
interface TopologyConfigurator
{
    /**
     * @param non-empty-string $endpoint
     * @param list<non-empty-string> $eventTypes
     */
    public function subscribeEndpointToEvents(string $endpoint, array $eventTypes): void;
}
