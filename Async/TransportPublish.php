<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\Envelope;

/**
 * @api
 */
interface TransportPublish
{
    /**
     * @param non-empty-list<Envelope> $envelopes
     */
    public function publish(array $envelopes): void;
}
