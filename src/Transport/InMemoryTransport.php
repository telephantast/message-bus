<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;

final class InMemoryTransport implements CommandSender, CommandReceiver, EventPublisher, EventReceiver
{
    /**
     * @var array<class-string, list<non-empty-string>>
     */
    private array $subscriptions = [];

    /**
     * @var array<non-empty-string, array<non-negative-int, Envelope<Command|Event>>>
     */
    private array $queues = [];

    /**
     * @var array<non-empty-string, callable(Envelope<Message<*>>): void>
     */
    private array $consumers = [];

    public bool $delivered { get => array_filter($this->queues) === []; }

    public function send(string $toEndpoint, array $commands): void
    {
        $queue = self::commandsQueue($toEndpoint);

        foreach ($commands as $command) {
            $this->queues[$queue][] = $command;
        }
    }

    public function consumeCommands(string $endpoint, callable $consumer): void
    {
        $queue = self::commandsQueue($endpoint);

        if (isset($this->consumers[$queue])) {
            throw new \LogicException();
        }

        /** @phpstan-ignore assign.propertyType */
        $this->consumers[$queue] = $consumer;
    }

    public function subscribe(string $endpoint, array $toEvents): void
    {
        $queue = self::eventsQueue($endpoint);

        foreach ($toEvents as $toEvent) {
            $this->subscriptions[$toEvent][] = $queue;
        }
    }

    public function publish(string $atEndpoint, array $events): void
    {
        foreach ($events as $event) {
            foreach ($this->subscriptions[$event->messageClass] ?? [] as $queue) {
                $this->queues[$queue][] = $event;
            }
        }
    }

    public function consumeEvents(string $endpoint, callable $consumer): void
    {
        $queue = self::eventsQueue($endpoint);

        if (isset($this->consumers[$queue])) {
            throw new \LogicException();
        }

        /** @phpstan-ignore assign.propertyType */
        $this->consumers[$queue] = $consumer;
    }

    private bool $delivering = false;

    public function deliver(): void
    {
        if ($this->delivering) {
            return;
        }

        $this->delivering = true;

        try {
            foreach ($this->consumers as $queue => $consumer) {
                foreach ($this->queues[$queue] ?? [] as $key => $message) {
                    $consumer($message);
                    unset($this->queues[$queue][$key]);
                }
            }
        } finally {
            $this->delivering = false;
        }
    }

    /**
     * @param non-empty-string $endpoint
     * @return non-empty-string
     */
    private static function commandsQueue(string $endpoint): string
    {
        return $endpoint . '@commands';
    }

    /**
     * @param non-empty-string $endpoint
     * @return non-empty-string
     */
    private static function eventsQueue(string $endpoint): string
    {
        return $endpoint . '@events';
    }
}
