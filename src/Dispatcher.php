<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

/**
 * @template-contravariant TSupportedMessages of Message = \Thesis\Message\Event
 */
abstract class Dispatcher
{
    /**
     * @template TResult
     * @param TSupportedMessages&Message<TResult> $message
     * @param list<Stamp> $stamps
     * @return TResult
     */
    final public function dispatch(Message $message, array $stamps = []): mixed
    {
        /** @phpstan-ignore argument.type */
        return $this->dispatchEnvelope(new Envelope($message, new Stamps($stamps)));
    }

    /**
     * @template TResult
     * @param Envelope<TSupportedMessages&Message<TResult>> $envelope
     * @return TResult
     */
    abstract public function dispatchEnvelope(Envelope $envelope): mixed;
}
