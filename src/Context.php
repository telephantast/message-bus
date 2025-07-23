<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Handler\Result;
use Thesis\MessageBus\Internal\ContextInvoke;
use Thesis\MessageBus\Internal\Wrapper;

/**
 * @template-contravariant TSupportedMethods of object = never
 * @template TTransaction of object = object
 * @implements Invoke<TSupportedMethods>
 */
final class Context implements Invoke
{
    /**
     * @param TTransaction $transaction
     * @param ContextInvoke<TSupportedMethods> $invoke
     */
    public function __construct(
        public readonly Endpoint $endpoint,
        private readonly object $transaction,
        public readonly string $persistenceKey,
        private readonly ContextInvoke $invoke,
        private readonly Wrapper $wrapper = new Wrapper(),
    ) {}

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
        return $this->invoke->invoke($this->wrapper->wrap($method), $this);
    }

    public function __invoke(object $method): mixed
    {
        return $this->invoke->invoke($this->wrapper->wrap($method), $this);
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
            transaction: $this->transaction,
            persistenceKey: $this->persistenceKey,
            invoke: $this->invoke,
            wrapper: $this->wrapper->withCause($method),
        );
        $child->commands = & $this->commands;
        $child->events = & $this->events;

        return $child;
    }
}
