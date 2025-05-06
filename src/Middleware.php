<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;
use Thesis\MessageBus\Handling\HandlingContext;
use Thesis\MessageBus\Persistence\Transaction;

/**
 * @api
 */
interface Middleware
{
    /**
     * @template TMessage of Message
     * @template TTransaction of Transaction
     * @param Envelope<TMessage> $envelope
     * @param HandlingContext<TTransaction> $context
     * @param Pipeline<TMessage, TTransaction> $pipeline
     */
    public function handle(Envelope $envelope, HandlingContext $context, Pipeline $pipeline): void;
}
