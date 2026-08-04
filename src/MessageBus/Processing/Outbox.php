<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Processing;

use Thesis\MessageBus\Transport\OutboundEnvelope;

/**
 * @api
 */
final readonly class Outbox
{
    public bool $dispatched;

    /**
     * @param list<OutboundEnvelope> $envelopes
     */
    public function __construct(
        public array $envelopes,
        bool $dispatched = false,
    ) {
        $this->dispatched = $this->envelopes === [] || $dispatched;
    }
}
