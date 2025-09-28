<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handlers;

use Amp\Cancellation;
use Thesis\MessageBus\Dispatch;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Envelope\Metadata;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Handlers;
use Thesis\MessageBus\IdGenerator;
use function Thesis\Formatter\formatReflectedFunction;
use function Thesis\Formatter\formatReflectedParameter;

/**
 * @api
 *
 * @template-contravariant Tx of object
 * @implements Handlers<Tx>
 */
final class InMemory implements Handlers
{
    /**
     * @var array<class-string, Handler<*, Tx>>
     */
    private array $handlers = [];

    public function has(string $messageClass): bool
    {
        return isset($this->handlers[$messageClass]);
    }

    /**
     * @phpstan-ignore return.unusedType
     */
    public function find(string $class): ?Handler
    {
        /** @phpstan-ignore return.type */
        return $this->handlers[$class] ?? null;
    }

    /**
     * @template T of object
     * @template WithTx of object
     * @param class-string<T> $messageClass
     * @param Handler<T, WithTx> $handler
     * @return self<Tx|WithTx>
     */
    public function with(string $messageClass, Handler $handler): self
    {
        if (isset($this->handlers[$messageClass])) {
            throw new \LogicException('TODO');
        }

        $handlers = clone $this;

        /** @phpstan-ignore assign.propertyType */
        $handlers->handlers[$messageClass] = $handler;

        return $handlers;
    }

    /**
     * @template T of object
     * @template WithTx of object
     * @param ?non-empty-string $name
     * @param callable(T, WithTx, Metadata, Cancellation): Dispatch $handler
     * @return self<Tx|WithTx>
     */
    public function withCallable(
        callable $handler,
        ?string $name = null,
        IdGenerator $idGenerator = new IdGenerator\Random(),
    ): self {
        $handlerRefl = new \ReflectionFunction($handler(...));
        $messageParamRefl = $handlerRefl->getParameters()[0] ?? null;

        if ($messageParamRefl === null) {
            throw new \LogicException('Failed to infer message class from ' . formatReflectedFunction($handlerRefl));
        }

        $messageClasses = self::reflectMessageClasses($messageParamRefl->getType());

        if ($messageClasses === []) {
            throw new \LogicException('Failed to infer message class from ' . formatReflectedParameter($messageParamRefl));
        }

        $handler = new Handler(
            name: $name ?? formatReflectedFunction($handlerRefl),
            /** @phpstan-ignore argument.type, argument.type */
            function: static fn(Envelope $e, object $tx, Cancellation $c) => $handler($e->payload, $tx, $e->metadata, $c),
            idGenerator: $idGenerator,
        );

        $handlers = $this;

        foreach ($messageClasses as $messageClass) {
            $handlers = $handlers->with($messageClass, $handler);
        }

        return $handlers;
    }

    /**
     * @return list<class-string>
     */
    private static function reflectMessageClasses(?\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionUnionType) {
            return array_merge(...array_map(self::reflectMessageClasses(...), $type->getTypes()));
        }

        if (!$type instanceof \ReflectionNamedType) {
            return [];
        }

        $name = $type->getName();

        if (class_exists($name)) {
            return [$name];
        }

        return [];
    }
}
