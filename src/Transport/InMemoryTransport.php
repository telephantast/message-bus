<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Envelope;

final class InMemoryTransport implements Sender, Subscriber, Publisher, Consumer
{
    /**
     * @var array<class-string, list<non-empty-string>>
     */
    private array $subscriptions = [];

    /**
     * @var array<non-empty-string, callable(Envelope<Command|Event>): void>
     */
    private array $consumers = [];

    /**
     * @var array<non-empty-string, array<non-negative-int, Envelope<Command|Event>>>
     */
    private array $messages = [];

    /**
     * @var array<non-empty-string, true>
     */
    private array $endpointsToDeliver = [];

    public function subscribe(string $endpoint, array $events): void
    {
        foreach ($events as $event) {
            $this->subscriptions[$event][] = $endpoint;
        }
    }

    public function send(array $routedCommands): void
    {
        foreach ($routedCommands as $routedCommand) {
            $this->messages[$routedCommand->endpoint][] = $routedCommand->envelope;
            $this->endpointsToDeliver[$routedCommand->endpoint] = true;
        }

        $this->deliver();
    }

    public function publish(array $events): void
    {
        foreach ($events as $event) {
            foreach ($this->subscriptions[$event->messageClass] ?? [] as $endpoint) {
                $this->messages[$endpoint][] = $event;
                $this->endpointsToDeliver[$endpoint] = true;
            }
        }

        $this->deliver();
    }

    public function consume(string $endpoint, callable $handler): void
    {
        if (isset($this->consumers[$endpoint])) {
            throw new \LogicException();
        }

        $this->consumers[$endpoint] = $handler;
        $this->endpointsToDeliver[$endpoint] = true;

        $this->deliver();
    }

    private bool $delivering = false;

    private function deliver(): void
    {
        if ($this->delivering) {
            return;
        }

        $this->delivering = true;

        while ($this->endpointsToDeliver !== []) {
            foreach ($this->endpointsToDeliver as $endpoint => $_) {
                unset($this->endpointsToDeliver[$endpoint]);

                $consumer = $this->consumers[$endpoint] ?? null;

                if ($consumer === null) {
                    return;
                }

                foreach ($this->messages[$endpoint] ?? [] as $index => $message) {
                    $consumer($message);
                    unset($this->messages[$endpoint][$index]);
                }
            }
        }

        $this->delivering = false;
    }
}
