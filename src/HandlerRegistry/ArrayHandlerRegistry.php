<?php

declare(strict_types=1);

namespace Thesis\MessageBus\HandlerRegistry;

use Thesis\Message\Message;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\HandlerRegistry;

/**
 * @api
 */
final class ArrayHandlerRegistry extends HandlerRegistry
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param array<class-string<TMessage>, Handler<TResult, TMessage>> $messageClassToHandler
     */
    public function __construct(
        private readonly array $messageClassToHandler = [],
    ) {}

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param class-string<TMessage> $messageClass
     * @return ?Handler<TResult, TMessage>
     */
    public function find(string $messageClass): ?Handler
    {
        /** @var ?Handler<TResult, TMessage> */
        return $this->messageClassToHandler[$messageClass] ?? null;
    }
}
