<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Handler;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler;
use Telephantast\MessageBus\MessageContext;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 * @implements Handler<TResult, TMessage>
 */
final class CallableHandler implements Handler
{
    /**
     * @param non-empty-string $id
     * @param callable(TMessage, MessageContext): TResult $callable
     */
    public function __construct(
        private readonly string $id,
        private readonly mixed $callable,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function handle(MessageContext $messageContext): mixed
    {
        return ($this->callable)($messageContext->getMessage(), $messageContext);
    }
}
