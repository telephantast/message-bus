<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

interface AsyncTransport
{
    /**
     * @param non-empty-list<Envelope> $envelopes
     */
    public function publish(array $envelopes): void;
}
