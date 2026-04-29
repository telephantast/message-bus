<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
interface Dispatcher
{
    /**
     * @param non-empty-list<OutgoingEnvelope> $envelopes
     */
    public function dispatch(array $envelopes): void;
}
