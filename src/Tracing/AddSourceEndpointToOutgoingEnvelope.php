<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Thesis\MessageBus\Dispatching\DispatchingContext;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class AddSourceEndpointToOutgoingEnvelope implements OutgoingEnvelopeProcessor
{
    public function process(Envelope $envelope, DispatchingContext $context): Envelope
    {
        return $envelope->withStamp(new SourceEndpoint($context->endpoint));
    }
}
