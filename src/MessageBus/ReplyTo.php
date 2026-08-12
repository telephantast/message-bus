<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Headers;
use const Thesis\MessageBus\Protocol\CONVERSATION_ID;
use const Thesis\MessageBus\Protocol\MESSAGE_ID;
use const Thesis\MessageBus\Protocol\ORIGIN_ENDPOINT;
use const Thesis\MessageBus\Protocol\RAW_CORRELATION_ID;
use const Thesis\MessageBus\Protocol\REPLY_TO_ENDPOINT;

/**
 * @api
 */
final readonly class ReplyTo
{
    public static function fromHeaders(Headers $headers): self
    {
        return new self(
            destination: $headers->find(REPLY_TO_ENDPOINT) ?? $headers->get(ORIGIN_ENDPOINT),
            correlationId: $headers->find(RAW_CORRELATION_ID) ?? $headers->get(MESSAGE_ID),
            conversationId: $headers->find(CONVERSATION_ID) ?? $headers->get(MESSAGE_ID),
        );
    }

    /**
     * @param non-empty-string $destination Endpoint name
     * @param non-empty-string $correlationId
     * @param non-empty-string $conversationId
     */
    public function __construct(
        public string $destination,
        public string $correlationId,
        public string $conversationId,
    ) {}
}
