<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class Outbox
{
    /**
     * @param non-empty-string $endpoint
     * @param non-empty-string $messageId
     * @param list<Envelope> $envelopes
     */
    public function __construct(
        public string $endpoint,
        public string $messageId,
        public array $envelopes,
    ) {}

    public function toEmpty(): self
    {
        return new self(
            endpoint: $this->endpoint,
            messageId: $this->messageId,
            envelopes: [],
        );
    }
}
