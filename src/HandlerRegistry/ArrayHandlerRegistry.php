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
     * @param array<class-string<Message>, Handler> $messageClassToHandler
     */
    public function __construct(
        private readonly array $messageClassToHandler = [],
    ) {}

    /**
     * @template TFindResult
     * @template TFindMessage of Message<TFindResult>
     * @param class-string<TFindMessage> $messageClass
     * @return ?Handler<TFindResult, TFindMessage>
     */
    public function find(string $messageClass): ?Handler
    {
        /** @var ?Handler<TFindResult, TFindMessage> */
        return $this->messageClassToHandler[$messageClass] ?? null;
    }
}
