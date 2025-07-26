<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Handler\MethodHandlers;
use Thesis\MessageBus\Handler\Result;
use Thesis\MessageBus\Internal\Wrapper;

/**
 * @template-contravariant TSupportedMethods of object = never
 * @template TTransaction of object = object
 * @implements Invoke<TSupportedMethods>
 */
final class Context implements Invoke
{
    /**
     * @template TStubSupportedMethods of object
     * @template TStubTransaction of object
     * @param NestedInvoke<TStubSupportedMethods> $invoke
     * @param TStubTransaction $transaction
     * @return self<TStubSupportedMethods, TStubTransaction>
     */
    public static function stub(
        NestedInvoke $invoke = new MethodHandlers(),
        object $transaction = new \stdClass(),
        string $persistenceKey = 'context.stub',
        Wrapper $wrapper = new Wrapper(),
        ?Endpoint $endpoint = null,
    ): self {
        return new self(
            endpoint: $endpoint ?? Endpoint::service('context.stub'),
            transactionFactory: static fn(): object => $transaction,
            persistenceKey: $persistenceKey,
            wrapper: $wrapper,
            childInvoke: $invoke,
        );
    }

    /**
     * @param callable(): TTransaction $transactionFactory
     * @param NestedInvoke<TSupportedMethods> $childInvoke
     */
    public function __construct(
        public readonly Endpoint $endpoint,
        private readonly mixed $transactionFactory,
        public readonly string $persistenceKey,
        private readonly Wrapper $wrapper,
        private readonly NestedInvoke $childInvoke,
    ) {}

    /**
     * @var ?TTransaction
     */
    private ?object $innerTransaction = null;

    /**
     * @var TTransaction
     */
    public object $transaction {
        get => $this->innerTransaction ??= ($this->transactionFactory)();
    }

    /**
     * @var list<Envelope>
     */
    public private(set) array $commands = [];

    /**
     * @no-named-arguments
     */
    public function send(object ...$commands): void
    {
        $this->commands = [
            ...$this->commands,
            ...array_map($this->wrapper->wrap(...), $commands),
        ];
    }

    /**
     * @var list<Envelope>
     */
    public private(set) array $events = [];

    /**
     * @no-named-arguments
     */
    public function publish(object ...$events): void
    {
        $this->events = [
            ...$this->events,
            ...array_map($this->wrapper->wrap(...), $events),
        ];
    }

    /**
     * @template TResult
     * @param TSupportedMethods|Envelope<TSupportedMethods> $method
     * @return ($method is (Method<TResult>|Envelope<Method<TResult>>) ? TResult : mixed)
     */
    public function invoke(object $method): mixed
    {
        return $this->childInvoke->nestedInvoke($this->wrapper->wrap($method), $this);
    }

    public function __invoke(object $method): mixed
    {
        return $this->childInvoke->nestedInvoke($this->wrapper->wrap($method), $this);
    }

    /**
     * @template TResult
     * @param Result<TResult> $result
     * @return TResult
     */
    public function processResult(Result $result): mixed
    {
        $this->send(...$result->commands);
        $this->publish(...$result->events);

        return $result->result;
    }

    public function child(Endpoint $endpoint, Envelope $method): static
    {
        $child = new self(
            endpoint: $endpoint,
            transactionFactory: $this->transactionFactory,
            persistenceKey: $this->persistenceKey,
            wrapper: $this->wrapper->withCause($method),
            childInvoke: $this->childInvoke,
        );
        $child->innerTransaction = &$this->innerTransaction;
        $child->commands = &$this->commands;
        $child->events = &$this->events;

        return $child;
    }
}
