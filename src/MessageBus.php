<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\MessageBus\Delivery;
use Thesis\MessageBus\Dispatcher;
use Thesis\MessageBus\Draft;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handlers;
use Thesis\MessageBus\IdGenerator;
use Thesis\MessageBus\Internal\Handler;

/**
 * @api
 *
 * @template-covariant Tx of object
 */
final readonly class MessageBus
{
    public const string DEFAULT_NAME = 'message_bus';

    /**
     * @param Delivery<Tx> $delivery
     * @param Handlers<Tx> $handlers
     * @param non-empty-string $name
     */
    public function __construct(
        private Dispatcher $dispatcher,
        private Delivery $delivery,
        private Handlers $handlers,
        private IdGenerator $idGenerator = new IdGenerator\Random(),
        private string $name = self::DEFAULT_NAME,
    ) {}

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
                Draft::class => $command->seal($this->name, $this->idGenerator),
                default => Draft::command($command)->seal($this->name, $this->idGenerator),
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
                Draft::class => $event->seal($this->name, $this->idGenerator),
                default => Draft::event($event)->seal($this->name, $this->idGenerator),
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

        $this->dispatcher->dispatch(array_map(
            fn(Draft|Envelope $message) => $message instanceof Envelope
                ? $message
                : $message->seal($this->name, $this->idGenerator),
            $messages,
        ));
    }

    /**
     * @template T of object
     * @param non-empty-string $consumer
     * @param Draft<T>|Envelope<T> $message
     */
    public function consume(string $consumer, Draft|Envelope $message): void
    {
        if ($message instanceof Draft) {
            $message = $message->seal($this->name, $this->idGenerator);
        }

        $this->delivery->consume($consumer, new Handler(
            name: $consumer,
            handlers: $this->handlers,
            idGenerator: $this->idGenerator,
        ), $message);
    }

    /**
     * @param non-empty-string $consumer
     * @return \Closure(): void Stop
     */
    public function startConsumer(string $consumer): \Closure
    {
        return $this->delivery->startConsumer($consumer, new Handler(
            name: $consumer,
            handlers: $this->handlers,
            idGenerator: $this->idGenerator,
        ))(...);
    }
}
