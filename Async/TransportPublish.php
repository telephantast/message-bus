<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

/**
 * @api
 */
interface TransportPublish
{
    /**
     * @param non-empty-list<PublishableEnvelope> $envelopes
     */
    public function publish(array $envelopes): void;
}
