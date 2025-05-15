<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\Message\Message;

/**
 * @template-contravariant TSupportedMessages of Message = Event
 * @template-covariant TTransaction of object = object
 * @implements Dispatcher<TSupportedMessages>
 */
final class HandlerContext implements Dispatcher
{
    /**
     * @var array<int, Command|Event>
     */
    private array $postponedMessages = [];

    /**
     * @param Dispatcher<TSupportedMessages> $dispatcher
     * @param TTransaction $transaction
     */
    public function __construct(
        private readonly Dispatcher $dispatcher,
        public readonly object $transaction = new \stdClass(),
    ) {}

    public function dispatch(Message $message, array $stamps = []): mixed
    {
        if ($message instanceof Command || $message instanceof Event) {
            $this->postponedMessages[] = $message;

            /** @phpstan-ignore return.type */
            return null;
        }

        if ($this->dispatcher instanceof MessageBus) {
            /** @phpstan-ignore argument.type */
            return $this->dispatcher->dispatch($message, $stamps, $this);
        }

        return $this->dispatcher->dispatch($message, $stamps);
    }

    /**
     * @internal
     * @todo this is a temporary hackish solution
     */
    public function dispatchPostponed(): void
    {
        while ($message = array_shift($this->postponedMessages)) {
            if ($this->dispatcher instanceof MessageBus) {
                /** @phpstan-ignore argument.type */
                $this->dispatcher->dispatch($message, context: $this);
            } else {
                /** @phpstan-ignore argument.type */
                $this->dispatcher->dispatch($message);
            }
        }
    }
}
