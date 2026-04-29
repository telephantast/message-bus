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
     * @var list<class-string>
     */
    public array $messageClasses { get => array_keys($this->handlers); }

    /**
     * @var array<class-string, \Closure(object, Context<Tx>): void>
     */
    private array $handlers = [];

    /**
     * @param Context<Tx> $context
     */
    public function handle(object $message, Context $context): void
    {
        ($this->handlers[$message::class] ?? throw new NoHandler($message::class))($message, $context);
    }

    /**
     * @template T of object
     * @template WithTx of object
     * @param class-string<T> $messageClass
     * @param callable(T, Context<WithTx>): void $handler
     * @return self<Tx|WithTx>
     */
    public function with(string $messageClass, callable $handler): self
    {
        if (isset($this->handlers[$messageClass])) {
            throw new HandlerAlreadyExists($messageClass);
        }

        $copy = clone $this;
        /** @phpstan-ignore assign.propertyType */
        $copy->handlers[$messageClass] = $handler(...);

        return $copy;
    }
}
