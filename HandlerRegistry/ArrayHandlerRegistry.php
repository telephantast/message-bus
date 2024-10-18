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
     * @param array<class-string<Message>, Handler> $messageClassToHandler
     */
    public function __construct(
        private readonly array $messageClassToHandler = [],
    ) {}

    public function find(string $messageClass): ?Handler
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->messageClassToHandler[$messageClass] ?? null;
    }
}
