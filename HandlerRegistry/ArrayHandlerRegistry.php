<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\HandlerRegistry;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler;
use Telephantast\MessageBus\HandlerRegistry;

/**
 * @api
 */
final class ArrayHandlerRegistry extends HandlerRegistry
{
    /**
     * @param array<class-string<Message>, Handler|callable(): Handler> $messageClassToHandlerMap
     */
    public function __construct(
        private array $messageClassToHandlerMap = [],
    ) {}

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param class-string<TMessage> $messageClass
     * @return ?Handler<TResult, TMessage>
     */
    public function find(string $messageClass): ?Handler
    {
        $handler = $this->messageClassToHandlerMap[$messageClass] ?? null;

        if ($handler instanceof \Closure) {
            /** @psalm-suppress MixedPropertyTypeCoercion */
            $handler = $this->messageClassToHandlerMap[$messageClass] = $handler();
        }

        /** @var ?Handler<TResult, TMessage> */
        return $handler;
    }
}
