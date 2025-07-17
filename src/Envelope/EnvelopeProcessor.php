<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
interface EnvelopeProcessor
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
