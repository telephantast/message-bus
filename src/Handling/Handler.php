<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Persistence\Transaction;

/**
 * @api
 * @template TMessage of Message = Message
 * @template TTransaction of Transaction = Transaction
 */
interface Handler
{
    /**
     * @return non-empty-string
     */
    public string $id { get; }

    /**
     * @param Envelope<TMessage> $envelope
     * @param HandlingContext<TTransaction> $context
     */
    public function handle(Envelope $envelope, HandlingContext $context): void;
}
