<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Event;
use Thesis\Message\Message;

/**
 * @template-contravariant TSupportedMessages of Message = Event
 */
interface Dispatcher
{
    /**
     * @template TResult
     * @param TSupportedMessages&Message<TResult> $message
     * @param list<Stamp> $stamps
     * @return TResult
     */
    public function dispatch(Message $message, array $stamps = []): mixed;
}
