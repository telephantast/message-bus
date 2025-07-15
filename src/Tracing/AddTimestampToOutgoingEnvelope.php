<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Psr\Clock\ClockInterface;
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

    public function process(string $endpoint, Envelope $envelope, ?Envelope $cause = null): Envelope
    {
        if ($envelope->stamps->has(Timestamp::class)) {
            return $envelope;
        }

        $stamp = new Timestamp($this->clock?->now() ?? new \DateTimeImmutable());

        return $envelope->withStamps($envelope->stamps->with($stamp));
    }
}
