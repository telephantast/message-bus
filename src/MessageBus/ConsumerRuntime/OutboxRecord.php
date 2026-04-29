<?php

declare(strict_types=1);

namespace Thesis\MessageBus\ConsumerRuntime;

use Thesis\MessageBus\OutgoingEnvelope;

/**
 * @api
 */
final readonly class OutboxRecord
{
    /**
     * @param non-empty-list<OutgoingEnvelope> $envelopes
     */
    public function __construct(
        public array $envelopes,
        public bool $dispatched = false,
    ) {}
}
