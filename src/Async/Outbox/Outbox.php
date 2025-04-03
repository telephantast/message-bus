<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\Outbox;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class Outbox
{
    /**
     * @param non-empty-string $messageId
     * @param list<Envelope<null, Message<null>>> $envelopes
     */
    public function __construct(
        public string $messageId,
        public string $queue,
        public array $envelopes = [],
    ) {}
}
