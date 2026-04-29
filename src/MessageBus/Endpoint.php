<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
final readonly class Endpoint
{
    /**
     * @param non-empty-string $name
     * @param Handlers<Tx> $handlers
     * @param Listeners<Tx> $listeners
     */
    public function __construct(
        public string $name,
        public Handlers $handlers = new Handlers(),
        public Listeners $listeners = new Listeners(),
    ) {}

    /**
     * @template T of object
     * @template WithTx of object
     * @param class-string<T> $messageClass
     * @param callable(T, Context<WithTx>): void $handler
     * @return self<Tx|WithTx>
     */
    public function withHandler(string $messageClass, callable $handler): self
    {
        return new self(
            name: $this->name,
            handlers: $this->handlers->with($messageClass, $handler),
            /** @phpstan-ignore argument.type */
            listeners: $this->listeners,
        );
    }

    /**
     * @template T of object
     * @template WithTx of object
     * @param class-string<T> $messageClass
     * @param callable(T, Context<WithTx>): void $listener
     * @return self<Tx|WithTx>
     */
    public function withListener(string $messageClass, callable $listener): self
    {
        return new self(
            name: $this->name,
            /** @phpstan-ignore argument.type */
            handlers: $this->handlers,
            listeners: $this->listeners->with($messageClass, $listener),
        );
    }
}
