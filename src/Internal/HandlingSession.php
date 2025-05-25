<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\HandlerContext;

/**
 * @internal
 * @psalm-internal Thesis\MessageBus
 * @template-contravariant TSupportedMessages of Message = \Thesis\Message\Event
 * @template-covariant TTransaction of object = object
 * @extends HandlerContext<TSupportedMessages, TTransaction>
 */
final class HandlingSession extends HandlerContext
{
    /**
     * @var array<int, Envelope>
     */
    private array $postponedEnvelopes = [];

    /**
     * @param Handler<TSupportedMessages, Message, TTransaction> $handler
     * @param TTransaction $transaction
     */
    public function __construct(
        private readonly Handler $handler,
        public readonly object $transaction,
    ) {}

    public function dispatchEnvelope(Envelope $envelope): mixed
    {
        if ($envelope->message instanceof Event || $envelope->message instanceof Command) {
            $this->postponedEnvelopes[] = $envelope;

            /** @phpstan-ignore return.type */
            return null;
        }

        /** @phpstan-ignore argument.type */
        return $this->handler->handle($envelope, $this);
    }

    public function dispatchPostponed(): void
    {
        while ($envelope = array_shift($this->postponedEnvelopes)) {
            $this->handler->handle($envelope, $this);
        }
    }
}
