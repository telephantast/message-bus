<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

use Telephantast\MessageBus\Stamp;

/**
 * @api
 * @psalm-immutable
 */
final readonly class MessageId implements Stamp
{
    /**
     * @param non-empty-string $messageId
     * @param ?non-empty-string $causationId
     * @param non-empty-string $correlationId
     */
    public function __construct(
        public string $messageId,
        public ?string $causationId,
        public string $correlationId,
    ) {}

    /**
     * @param non-empty-string $messageId
     */
    public static function initial(string $messageId): self
    {
        return new self($messageId, null, $messageId);
    }

    /**
     * @param non-empty-string $messageId
     */
    public static function fromCause(string $messageId, ?self $cause): self
    {
        return new self(
            messageId: $messageId,
            causationId: $cause?->messageId,
            correlationId: $cause?->correlationId ?? $messageId,
        );
    }
}
