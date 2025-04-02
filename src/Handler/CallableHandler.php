<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Message;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\MessageContext;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 * @implements Handler<TResult, TMessage>
 */
final readonly class CallableHandler implements Handler
{
    /**
     * @param non-empty-string $id
     * @param callable(TMessage, MessageContext<TResult, TMessage>): TResult $callable
     */
    public function __construct(
        private string $id,
        private mixed $callable,
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
