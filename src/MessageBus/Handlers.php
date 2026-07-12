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
    public array $payloadClasses { get => array_keys($this->handlers); }

    /**
     * @var array<class-string, \Closure(Envelope, Context<Tx>): void>
     */
    private array $handlers = [];

    /**
     * @param Context<Tx> $context
     */
    public function handle(Envelope $envelope, Context $context): void
    {
        $payloadClass = $envelope->payload::class;
        ($this->handlers[$payloadClass] ?? throw new NoHandler($payloadClass))($envelope, $context);
    }

    /**
     * @template T of object
     * @template WithTx of object
     * @param class-string<T> $payloadClass
     * @param callable(Envelope<T>, Context<WithTx>): void $handler
     * @return self<Tx|WithTx>
     */
    public function with(string $payloadClass, callable $handler): self
    {
        if (isset($this->handlers[$payloadClass])) {
            throw new HandlerAlreadyExists($payloadClass);
        }

        $copy = clone $this;

        /** @phpstan-ignore assign.propertyType */
        $copy->handlers[$payloadClass] = $handler(...);

        return $copy;
    }
}
