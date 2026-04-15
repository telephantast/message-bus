<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Delivery\Outbox;

use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class Record
{
    /**
     * @param non-empty-list<Envelope> $messages
     */
    public function __construct(
        public array $messages,
        public bool $dispatched,
    ) {}
}
