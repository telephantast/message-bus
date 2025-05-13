<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Event;
use Thesis\Message\Message;

/**
 * @template-contravariant TSupportedMessages of Message = Event
 * @template-covariant TTransaction of object = object
 * @implements Dispatcher<TSupportedMessages>
 */
final readonly class HandlerContext implements Dispatcher
{
    /**
     * @param Dispatcher<TSupportedMessages> $dispatcher
     * @param TTransaction $transaction
     */
    public function __construct(
        private Dispatcher $dispatcher,
        public object $transaction = new \stdClass(),
    ) {}

    public function dispatch(Message $message, array $stamps = []): mixed
    {
        if ($this->dispatcher instanceof MessageBus) {
            /** @phpstan-ignore argument.type */
            return $this->dispatcher->dispatch($message, $stamps, $this);
        }

        return $this->dispatcher->dispatch($message, $stamps);
    }
}
