<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Headers;

/**
 * @api
 */
final readonly class ReplyTo
{
    public static function fromHeaders(Headers $headers): self
    {
        return new self(
            destinationEndpoint: $headers->find(REPLY_TO_ENDPOINT) ?? $headers->get(ORIGIN_ENDPOINT),
            correlationId: $headers->find(CORRELATION_ID) ?? $headers->get(MESSAGE_ID),
            conversationId: $headers->find(CONVERSATION_ID) ?? $headers->get(MESSAGE_ID),
        );
    }

    /**
     * @param non-empty-string $destinationEndpoint
     * @param non-empty-string $correlationId
     * @param non-empty-string $conversationId
     */
    public function __construct(
        public string $destinationEndpoint,
        public string $correlationId,
        public string $conversationId,
    ) {}
}
