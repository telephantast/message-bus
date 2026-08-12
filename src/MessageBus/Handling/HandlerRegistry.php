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
     * @param ?non-empty-string $qualifier
     * @return list<callable(T, HandlerContext, Tx): void>
     */
    public function findHandlers(string $messageClass, ?string $qualifier = null): array;
}
