<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class AddMessageIdToOutgoingEnvelope implements OutgoingEnvelopeProcessor
{
    public function __construct(
        private MessageIdGenerator $messageIdGenerator = new RandomMessageIdGenerator(),
    ) {}

    public function process(string $endpoint, Envelope $envelope, ?Envelope $cause = null): Envelope
    {
        if ($envelope->stamps->has(MessageId::class)) {
            return $envelope;
        }

        $stamp = new MessageId($this->messageIdGenerator->generateMessageId());

        return $envelope->withStamps($envelope->stamps->with($stamp));
    }
}
