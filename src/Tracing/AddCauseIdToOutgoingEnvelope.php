<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Thesis\MessageBus\Dispatching\DispatchingContext;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class AddCauseIdToOutgoingEnvelope implements OutgoingEnvelopeProcessor
{
    public function process(Envelope $envelope, DispatchingContext $context): Envelope
    {
        if ($envelope->hasStamp(CauseId::class)) {
            return $envelope;
        }

        return $envelope->withStamp(new CauseId($context->cause?->messageId));
    }
}
