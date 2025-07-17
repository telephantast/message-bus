<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\Message\Call;
use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;

/**
 * @internal
 */
final readonly class Dispatcher
{
    /**
     * @param array<non-empty-string, Endpoint> $endpoints
     */
    public function __construct(
        private array $endpoints = [],
    ) {}

    /**
     * @param non-empty-string $endpoint
     * @param non-empty-list<class-string<Event>> $toEvents
     */
    public function dispatchSubscription(string $endpoint, array $toEvents): void
    {
        // todo group subscriptions by endpoint, memoize

        foreach ($toEvents as $toEvent) {
            foreach ($this->endpoints as $eachEndpoint) {
                if ($eachEndpoint->publishesEvent($toEvent)) {
                    $eachEndpoint->subscribe($endpoint, [$toEvent]);

                    continue 2;
                }
            }

            throw new \LogicException(\sprintf('Publisher of `%s` not found', $toEvent));
        }
    }

    /**
     * @param non-empty-list<Envelope<Command>> $commands
     */
    public function dispatchCommands(array $commands): void
    {
        // todo group commands by endpoint, memoize

        foreach ($commands as $command) {
            foreach ($this->endpoints as $endpoint) {
                if ($endpoint->handlesCommand($command->messageClass)) {
                    $endpoint->send([$command]);

                    continue 2;
                }
            }

            throw new \LogicException(\sprintf('Failed to route command `%s`', $command->messageClass));
        }
    }

    /**
     * @template TResult
     * @param Envelope<Call<TResult>> $call
     * @return TResult
     */
    public function dispatchCall(Envelope $call, ?Context $parentContext = null): mixed
    {
        foreach ($this->endpoints as $endpoint) {
            if ($endpoint->handlesCall($call->messageClass)) {
                return $endpoint->invoke($call, $this, $parentContext);
            }
        }

        throw new \LogicException(\sprintf('Failed to route call `%s`', $call->messageClass));
    }

    /**
     * @template TResult
     * @param Envelope<Call<TResult>> $call
     * @return TResult
     */
    public function dispatchChildCall(Envelope $call, Context $parentContext): mixed
    {
        foreach ($this->endpoints as $endpoint) {
            if ($endpoint->handlesCall($call->messageClass)) {
                return $endpoint->invoke($call, $this, $parentContext);
            }
        }

        throw new \LogicException(\sprintf('Failed to route call `%s`', $call->messageClass));
    }
}
