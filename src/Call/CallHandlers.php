<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Call;

use Thesis\MessageBus\Call;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Invoker;
use Thesis\MessageBus\Result;
use function Typhoon\Formatter\formatClass;

/**
 * @template-contravariant TCalls of Call = never
 * @extends Invoker<TCalls>
 * @implements CallHandler<TCalls>
 */
final class CallHandlers extends Invoker implements CallHandler
{
    /**
     * @var array<class-string<Call>, CallHandler<*>>
     */
    private(set) public array $handlers = [];

    /**
     * @template TCall of Call
     * @param non-empty-list<class-string<TCall>> $calls
     * @param CallHandler<TCall> $handler
     * @return self<TCalls|TCall>
     */
    public function with(array $calls, CallHandler $handler): self
    {
        $copy = clone $this;

        foreach ($calls as $call) {
            if (isset($copy->handlers[$call])) {
                throw new \LogicException(\sprintf(
                    'Handler for call `%s` already exists: `%s`',
                    $call,
                    formatClass($handler),
                ));
            }

            $copy->handlers[$call] = $handler;
        }

        return $copy;
    }

    public function handle(Envelope $call, Invoker $invoker): Result
    {
        $handler = $this->handlers[$call->messageClass]
            ?? throw new \LogicException(\sprintf('No handler for call `%s`', $call->messageClass));

        /** @phpstan-ignore return.type, argument.type */
        return $handler->handle($call, $invoker);
    }

    protected function invokeEnvelope(Envelope $call): mixed
    {
        return $this->handle($call, $this)->result;
    }
}
