<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

/**
 * @api
 */
final readonly class SplitTransport implements Dispatcher, Receiver, TopologyConfigurator
{
    public function __construct(
        private Dispatcher $dispatcher,
        private Receiver $receiver,
        private TopologyConfigurator $topologyConfigurator,
    ) {}

    public function dispatch(array $envelopes): void
    {
        $this->dispatcher->dispatch($envelopes);
    }

    public function startConsumer(string $endpoint, callable $handler): Consumer
    {
        return $this->receiver->startConsumer($endpoint, $handler);
    }

    public function subscribeEndpointToEvents(string $endpoint, array $eventTypes): void
    {
        $this->topologyConfigurator->subscribeEndpointToEvents($endpoint, $eventTypes);
    }
}
