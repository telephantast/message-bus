<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class EnvelopeProcessors implements EnvelopeProcessor
{
    /**
     * @param iterable<EnvelopeProcessor> $processors
     */
    public function __construct(
        public iterable $processors,
    ) {}

    public function process(string $endpoint, Envelope $envelope, ?Envelope $cause = null): Envelope
    {
        foreach ($this->processors as $processor) {
            $envelope = $processor->process($endpoint, $envelope, $cause);
        }

        return $envelope;
    }
}
