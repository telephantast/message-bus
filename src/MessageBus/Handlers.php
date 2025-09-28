<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
interface Handlers
{
    public function has(string $messageClass): bool;

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ?Handler<T, Tx>
     */
    public function find(string $class): ?Handler;
}
