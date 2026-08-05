<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\MessageBus\HandlerContext;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface HandlerRegistry
{
    /**
     * @var list<class-string>
     */
    public array $messageClasses { get; }

    /**
     * @template T of object
     * @param class-string<T> $messageClass
     * @return ?callable(T, HandlerContext, TransactionScope<Tx>): void null when no handler is registered for the message class
     */
    public function handlerFor(string $messageClass): ?callable;
}
