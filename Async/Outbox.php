<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\Envelope;

/**
 * @api
 */
interface Outbox
{
    public function add(Envelope $envelope): void;

    /**
     * @return list<Envelope>
     */
    public function all(): array;

    /**
     * @param non-empty-string $messageId
     */
    public function remove(string $messageId): void;
}
