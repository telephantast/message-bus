<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Event;

use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Invoker;
use Thesis\MessageBus\Result;

/**
 * @implements EventListener<object>
 */
final class EventListeners implements EventListener
{
    /**
     * @var array<class-string, list<EventListener<*>>>
     */
    private(set) public array $listeners = [];

    /**
     * @template TEvent of object
     * @param non-empty-list<class-string<TEvent>> $calls
     * @param EventListener<TEvent> $listener
     */
    public function with(array $calls, EventListener $listener): self
    {
        $copy = clone $this;

        foreach ($calls as $call) {
            $copy->listeners[$call][] = $listener;
        }

        return $copy;
    }

    public function on(Envelope $event, Invoker $invoker): Result
    {
        $commandEnvelopeLists = [];
        $eventEnvelopeLists = [];

        foreach ($this->listeners[$event->messageClass] ?? [] as $listener) {
            /** @phpstan-ignore argument.type */
            $result = $listener->on($event, $invoker);
            $commandEnvelopeLists[] = $result->commandEnvelopes;
            $eventEnvelopeLists[] = $result->eventEnvelopes;
        }

        return new Result(
            commandEnvelopes: array_merge(...$commandEnvelopeLists),
            eventEnvelopes: array_merge(...$eventEnvelopeLists),
        );
    }
}
