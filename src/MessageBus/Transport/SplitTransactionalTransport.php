<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

/**
 * @api
 *
 * @template-contravariant Tx of object
 * @implements TransactionalDispatcher<Tx>
 */
final readonly class SplitTransactionalTransport implements TransactionalDispatcher, Receiver, TopologyConfigurator
{
    /**
     * @param TransactionalDispatcher<Tx> $dispatcher
     */
    public function __construct(
        private TransactionalDispatcher $dispatcher,
        private Receiver $receiver,
        private TopologyConfigurator $topologyConfigurator,
    ) {}

    public function dispatch(array $envelopes): void
    {
        $this->dispatcher->dispatch($envelopes);
    }

    public function dispatchInTransaction(object $transaction, array $envelopes): void
    {
        $this->dispatcher->dispatchInTransaction($transaction, $envelopes);
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
