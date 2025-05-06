<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Psr\Clock\ClockInterface;
use Thesis\MessageBus\Dispatching\DispatchingContext;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;
use Thesis\Time\WallClock;

/**
 * @api
 */
final readonly class AddTimestampToOutgoingEnvelope implements OutgoingEnvelopeProcessor
{
    public function __construct(
        private ClockInterface $clock = new WallClock(),
    ) {}

    public function process(Envelope $envelope, DispatchingContext $context): Envelope
    {
        if ($envelope->hasStamp(Timestamp::class)) {
            return $envelope;
        }

        return $envelope->withStamp(new Timestamp($this->clock->now()));
    }
}
