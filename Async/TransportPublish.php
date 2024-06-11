<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

/**
 * @api
 */
interface TransportPublish
{
    /**
     * @param non-empty-list<OutgoingEnvelope> $outgoingEnvelopes
     */
    public function publish(array $outgoingEnvelopes): void;
}
