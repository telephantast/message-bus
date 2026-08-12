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
     * @var array<class-string, non-empty-list<array{?non-empty-string, callable(object, HandlerContext, Tx): void}>>
     */
    private array $handlers = [];

    /**
     * @template T of object
     * @param class-string<T> $messageClass
     * @param callable(T, HandlerContext, Tx): void $handler
     * @param ?non-empty-string $qualifier
     * @return self<Tx>
     */
    public function with(string $messageClass, callable $handler, ?string $qualifier = null): self
    {
        if ($qualifier !== null) {
            foreach ($this->handlers[$messageClass] ?? [] as [$existingQualifier]) {
                if ($existingQualifier === $qualifier) {
                    throw new \LogicException(\sprintf(
                        'Handler qualifier "%s" is already registered for message "%s".',
                        $qualifier,
                        $messageClass,
                    ));
                }
            }
        }

        $copy = clone $this;
        /** @phpstan-ignore assign.propertyType */
        $copy->handlers[$messageClass][] = [$qualifier, $handler];

        return $copy;
    }

    public function handlerFor(string $messageClass, ?string $qualifier = null): ?callable
    {
        $qualifierAndHandlers = $this->handlers[$messageClass] ?? [];

        if ($qualifierAndHandlers === []) {
            return null;
        }

        if ($qualifier !== null) {
            foreach ($qualifierAndHandlers as [$handlerQualifier, $handler]) {
                if ($handlerQualifier === $qualifier) {
                    return $handler;
                }
            }

            return null;
        }

        $handlers = array_column($qualifierAndHandlers, 1);

        if (\count($handlers) === 1) {
            return $handlers[0];
        }

        return static function (object $msg, HandlerContext $ctx, object $tx) use ($handlers): void {
            foreach ($handlers as $handler) {
                /** @phpstan-ignore argument.type */
                $handler($msg, $ctx, $tx);
            }
        };
    }
}
