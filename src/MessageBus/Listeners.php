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
    public array $messageClasses { get => array_keys($this->listeners); }

    /**
     * @var array<class-string, non-empty-list<\Closure(object, Context<Tx>): void>>
     */
    private array $listeners = [];

    /**
     * @param Context<Tx> $context
     */
    public function on(object $event, Context $context): void
    {
        foreach ($this->listeners[$event::class] ?? [] as $listener) {
            $listener($event, $context);
        }
    }

    /**
     * @template T of object
     * @template WithTx of object
     * @param class-string<T> $messageClass
     * @param callable(T, Context<WithTx>): void $listener
     * @return self<Tx|WithTx>
     */
    public function with(string $messageClass, callable $listener): self
    {
        $copy = clone $this;
        /** @phpstan-ignore assign.propertyType */
        $copy->listeners[$messageClass][] = $listener(...);

        return $copy;
    }
}
