<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\Envelope;

/**
 * @api
 */
final readonly class Publish
{
    public function __construct(
        private TransportPublish $publish,
        private ExchangeResolver $exchangeResolver,
    ) {}

    /**
     * @param non-empty-list<Envelope> $envelopes
     */
    public function publish(array $envelopes): void
    {
        $this->publish->publish(array_map(
            fn(Envelope $envelope): OutgoingEnvelope => new OutgoingEnvelope(
                exchange: $this->exchangeResolver->resolve($envelope->getMessageClass()),
                envelope: $envelope,
            ),
            $envelopes,
        ));
    }
}
