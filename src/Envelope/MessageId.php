<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class MessageId
{
    /**
     * @param non-empty-string $messageId
     */
    public function __construct(
        public string $messageId,
    ) {}
}
