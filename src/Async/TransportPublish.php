<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use Thesis\MessageBus\Envelope;

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
