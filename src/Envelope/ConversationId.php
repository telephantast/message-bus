<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

use Thesis\MessageBus\Stamp;

/**
 * @api
 */
final readonly class ConversationId implements Stamp
{
    /**
     * @param non-empty-string $conversationId
     */
    public function __construct(
        public string $conversationId,
    ) {}
}
