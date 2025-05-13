<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;
use Thesis\MessageBus\Handling\HandleContext;

/**
 * @api
 */
interface Middleware
{
    /**
     * @template TMessage of Message
     * @template TWrappedTransaction of object
     * @param Envelope<TMessage> $envelope
     * @param HandleContext<TWrappedTransaction> $context
     * @param Pipeline<TMessage, TWrappedTransaction> $pipeline
     */
    public function handle(Envelope $envelope, HandleContext $context, Pipeline $pipeline): void;
}
