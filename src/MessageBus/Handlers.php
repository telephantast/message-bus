<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Exception\NoHandler;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
final class Handlers
{
    /**
     * @var array<non-empty-string, array<class-string, \Closure(Envelope, Context<Tx>): void>>
     */
    private array $handlers = [];

    /**
     * @template T of object
     * @param non-empty-string $consumer
     * @param class-string<T> $messageClass
     * @return \Closure(Envelope<T>, Context<Tx>): void
     */
    public function get(string $consumer, string $messageClass): \Closure
    {
        return $this->handlers[$consumer][$messageClass]
            ?? throw new NoHandler($messageClass);
    }

    /**
     * @template T of object
     * @template WithTx of object
     * @param non-empty-string $consumer
     * @param class-string<T> $messageClass
     * @param callable(Envelope<T>, Context<WithTx>): void $handler
     * @return self<Tx|WithTx>
     */
    public function with(string $consumer, string $messageClass, callable $handler): self
    {
        if (isset($this->handlers[$consumer][$messageClass])) {
            throw new \LogicException('TODO');
        }

        $handlers = clone $this;

        /** @phpstan-ignore assign.propertyType */
        $handlers->handlers[$consumer][$messageClass] = $handler(...);

        return $handlers;
    }
}
