<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class EnsureOutgoingEnvelopeHasMessageId implements OutgoingEnvelopeProcessor
{
    public function process(string $endpoint, Envelope $envelope, ?Envelope $cause = null): Envelope
    {
        if (!$envelope->stamps->has(MessageId::class)) {
            throw new \LogicException('No message id');
        }

        return $envelope;
    }
}
