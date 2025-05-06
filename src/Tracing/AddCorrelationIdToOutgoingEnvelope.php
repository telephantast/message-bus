<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Thesis\MessageBus\Dispatching\DispatchingContext;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class AddCorrelationIdToOutgoingEnvelope implements OutgoingEnvelopeProcessor
{
    public function process(Envelope $envelope, DispatchingContext $context): Envelope
    {
        if ($envelope->hasStamp(CorrelationId::class)) {
            return $envelope;
        }

        if ($context->cause === null) {
            return $envelope->withStamp(new CorrelationId($envelope->messageId));
        }

        $correlationId = $context->cause->findStamp(CorrelationId::class)
            ?? new CorrelationId($context->cause->messageId);

        return $envelope->withStamp($correlationId);
    }
}
