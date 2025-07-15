<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class AddCauseIdToOutgoingEnvelope implements OutgoingEnvelopeProcessor
{
    public function process(string $endpoint, Envelope $envelope, ?Envelope $cause = null): Envelope
    {
        if ($envelope->stamps->has(CauseId::class)) {
            return $envelope;
        }

        $stamp = new CauseId($cause?->messageId);

        return $envelope->withStamps($envelope->stamps->with($stamp));
    }
}
