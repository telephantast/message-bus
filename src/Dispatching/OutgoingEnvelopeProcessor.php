<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Dispatching;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
interface OutgoingEnvelopeProcessor
{
    /**
     * @template TMessage of Message
     * @param non-empty-string $endpoint
     * @param Envelope<TMessage> $envelope
     * @param ?Envelope<*> $cause
     * @return Envelope<TMessage>
     */
    public function process(string $endpoint, Envelope $envelope, ?Envelope $cause = null): Envelope;
}
