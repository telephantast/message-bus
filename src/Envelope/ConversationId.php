<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class ConversationId
{
    /**
     * @param non-empty-string $conversationId
     */
    public function __construct(
        public string $conversationId,
    ) {}
}
