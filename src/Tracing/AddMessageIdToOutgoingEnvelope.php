<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Thesis\MessageBus\Dispatching\DispatchContext;
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

    public function process(Envelope $envelope, DispatchContext $context): Envelope
    {
        if ($envelope->hasStamp(MessageId::class)) {
            return $envelope;
        }

        return $envelope->withStamp(new MessageId($this->messageIdGenerator->generateMessageId()));
    }
}
