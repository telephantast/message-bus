<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Thesis\MessageBus\Dispatching\DispatchContext;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class AddSourceEndpointToOutgoingEnvelope implements OutgoingEnvelopeProcessor
{
    public function process(Envelope $envelope, DispatchContext $context): Envelope
    {
        return $envelope->withStamp(new SourceEndpoint($context->endpoint));
    }
}
