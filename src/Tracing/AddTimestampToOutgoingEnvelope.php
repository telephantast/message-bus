<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Psr\Clock\ClockInterface;
use Thesis\MessageBus\Dispatching\DispatchContext;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class AddTimestampToOutgoingEnvelope implements OutgoingEnvelopeProcessor
{
    public function __construct(
        private ?ClockInterface $clock = null,
    ) {}

    public function process(Envelope $envelope, DispatchContext $context): Envelope
    {
        if ($envelope->hasStamp(Timestamp::class)) {
            return $envelope;
        }

        return $envelope->withStamp(new Timestamp($this->clock?->now() ?? new \DateTimeImmutable()));
    }
}
