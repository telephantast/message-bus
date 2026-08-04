<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

/**
 * @api
 */
interface Dispatcher
{
    /**
     * Returns after the transport confirms that all envelopes have been sent.
     *
     * @param non-empty-list<OutboundEnvelope> $envelopes
     */
    public function dispatch(array $envelopes): void;
}
