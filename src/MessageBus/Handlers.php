<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Exception\HandlerAlreadyExists;
use Thesis\MessageBus\Exception\NoHandler;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
final class Handlers
{
    /**
     * @var array<class-string, \Closure(Envelope, Context<Tx>): void>
     */
    private array $handlers = [];

    /**
     * @param class-string $messageClass
     */
    public function has(string $messageClass): bool
    {
        return isset($this->handlers[$messageClass]);
    }

    /**
     * @template T of object
     * @param class-string<T> $messageClass
     * @return \Closure(Envelope<T>, Context<Tx>): void
     */
    public function get(string $messageClass): \Closure
    {
        return $this->handlers[$messageClass] ?? throw new NoHandler($messageClass);
    }

    /**
     * @template T of object
     * @template WithTx of object
     * @param class-string<T> $messageClass
     * @param callable(Envelope<T>, Context<WithTx>): void $handler
     * @return self<Tx|WithTx>
     */
    public function with(string $messageClass, callable $handler): self
    {
        if (isset($this->handlers[$messageClass])) {
            throw new HandlerAlreadyExists($messageClass);
        }

        $handlers = clone $this;
        /** @phpstan-ignore assign.propertyType */
        $handlers->handlers[$messageClass] = $handler(...);

        return $handlers;
    }
}
