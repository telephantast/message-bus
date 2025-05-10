<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Dispatching;

use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class OutgoingEnvelopeProcessors implements OutgoingEnvelopeProcessor
{
    /**
     * @param iterable<OutgoingEnvelopeProcessor> $processors
     */
    public function __construct(
        public iterable $processors,
    ) {}

    public function process(Envelope $envelope, DispatchContext $context): Envelope
    {
        foreach ($this->processors as $processor) {
            $envelope = $processor->process($envelope, $context);
        }

        return $envelope;
    }
}
