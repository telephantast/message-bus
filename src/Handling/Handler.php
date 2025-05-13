<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;

/**
 * @api
 * @template TMessage of Message = Message
 * @template TWrappedTransaction of object = object
 */
interface Handler
{
    /**
     * @return non-empty-string
     */
    public string $id { get; }

    /**
     * @param Envelope<TMessage> $envelope
     * @param HandleContext<TWrappedTransaction> $context
     */
    public function handle(Envelope $envelope, HandleContext $context): void;
}
