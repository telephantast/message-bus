<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class AddConversationIdToOutgoingEnvelope implements OutgoingEnvelopeProcessor
{
    public function process(string $endpoint, Envelope $envelope, ?Envelope $cause = null): Envelope
    {
        if ($envelope->stamps->has(ConversationId::class)) {
            return $envelope;
        }

        $stamp = match ($cause) {
            null => new ConversationId($envelope->messageId),
            default => $cause->stamps->find(ConversationId::class) ?? new ConversationId($cause->messageId)
        };

        return $envelope->withStamps($envelope->stamps->with($stamp));
    }
}
