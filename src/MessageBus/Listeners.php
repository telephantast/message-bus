<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
final class Listeners
{
    /**
     * @var list<class-string>
     */
    public array $payloadClasses { get => array_keys($this->listeners); }

    /**
     * @var array<class-string, non-empty-list<\Closure(Envelope, Context<Tx>): void>>
     */
    private array $listeners = [];

    /**
     * @param Context<Tx> $context
     */
    public function on(Envelope $envelope, Context $context): void
    {
        foreach ($this->listeners[$envelope->payload::class] ?? [] as $listener) {
            $listener($envelope, $context);
        }
    }

    /**
     * @template T of object
     * @template WithTx of object
     * @param class-string<T> $payloadClass
     * @param callable(Envelope<T>, Context<WithTx>): void $listener
     * @return self<Tx|WithTx>
     */
    public function with(string $payloadClass, callable $listener): self
    {
        $copy = clone $this;

        /** @phpstan-ignore assign.propertyType */
        $copy->listeners[$payloadClass][] = $listener(...);

        return $copy;
    }
}
