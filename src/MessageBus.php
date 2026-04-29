<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\MessageBus\Consumer;
use Thesis\MessageBus\Dispatcher;
use Thesis\MessageBus\Draft;
use Thesis\MessageBus\Endpoint;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Exception\NoEndpoint;
use Thesis\MessageBus\IdGenerator;

/**
 * @api
 */
final readonly class MessageBus
{
    /**
     * @var array<non-empty-string, Endpoint<*>>
     */
    private array $endpoints;

    /**
     * @param non-empty-string $name
     * @param list<Endpoint<*>> $endpoints
     */
    public function __construct(
        private Dispatcher $dispatcher,
        array $endpoints = [],
        private string $name = 'message_bus',
        private IdGenerator $idGenerator = new IdGenerator\UuidV7(),
    ) {
        $this->endpoints = array_column($endpoints, null, 'name');
    }

    /**
     * @no-named-arguments
     */
    public function send(object ...$commands): void
    {
        if ($commands === []) {
            return;
        }

        $this->dispatcher->dispatch(array_map(
            fn(object $command) => match ($command::class) {
                Envelope::class => $command,
                Draft::class => $this->seal($command),
                default => $this->seal(Draft::command($command)),
            },
            $commands,
        ));
    }

    /**
     * @no-named-arguments
     */
    public function publish(object ...$events): void
    {
        if ($events === []) {
            return;
        }

        $this->dispatcher->dispatch(array_map(
            fn(object $event) => match ($event::class) {
                Envelope::class => $event,
                Draft::class => $this->seal($event),
                default => $this->seal(Draft::event($event)),
            },
            $events,
        ));
    }

    /**
     * @no-named-arguments
     */
    public function dispatch(Draft|Envelope ...$messages): void
    {
        if ($messages === []) {
            return;
        }

        $this->dispatcher->dispatch(array_map($this->seal(...), $messages));
    }

    /**
     * @param ?non-empty-string $endpoint
     */
    public function consume(Draft|Envelope $message, ?string $endpoint = null): void
    {
        $message = $this->seal($message);

        if ($endpoint === null) {
            $this->endpointHandling($message->payload::class)->consume($message);

            return;
        }

        $this->endpoint($endpoint)->consume($message);
    }

    /**
     * @param non-empty-string $endpoint
     */
    public function startConsumer(string $endpoint): Consumer
    {
        return $this->endpoint($endpoint)->startConsumer();
    }

    /**
     * @template T of object
     * @param Draft<T>|Envelope<T> $message
     * @return Envelope<T>
     */
    private function seal(Draft|Envelope $message): Envelope
    {
        if ($message instanceof Draft) {
            return $message->seal($this->name, $this->idGenerator);
        }

        return $message;
    }

    /**
     * @param non-empty-string $name
     * @return Endpoint<*>
     */
    private function endpoint(string $name): Endpoint
    {
        return $this->endpoints[$name] ?? throw new NoEndpoint($name);
    }

    /**
     * @param class-string $messageClass
     * @return Endpoint<*>
     */
    private function endpointHandling(string $messageClass): Endpoint
    {
        foreach ($this->endpoints as $endpoint) {
            if ($endpoint->handles($messageClass)) {
                return $endpoint;
            }
        }

        throw new NoEndpoint();
    }
}
