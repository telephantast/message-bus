<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\MessageBus\HandlerContext;

/**
 * @api
 *
 * @template Tx of object = object
 * @implements HandlerRegistry<Tx>
 */
final class Handlers implements HandlerRegistry
{
    /**
     * @template STx of object
     * @param class-string<STx> $txClass
     * @return self<STx>
     */
    public static function tx(string $txClass): self
    {
        /** @var self<STx> */
        return new self();
    }

    /**
     * @var list<class-string>
     */
    public array $messageClasses {
        get => array_keys($this->handlers);
    }

    /**
     * @var array<class-string, callable(object, HandlerContext, TransactionScope<Tx>): void>
     */
    private array $handlers = [];

    /**
     * @template T of object
     * @param class-string<T> $messageClass
     * @param callable(T, HandlerContext, TransactionScope<Tx>): void $handler
     * @return self<Tx>
     */
    public function with(string $messageClass, callable $handler): self
    {
        $copy = clone $this;
        /** @phpstan-ignore assign.propertyType */
        $copy->handlers[$messageClass] = $handler;

        return $copy;
    }

    public function handlerFor(string $messageClass): ?callable
    {
        return $this->handlers[$messageClass] ?? null;
    }
}
