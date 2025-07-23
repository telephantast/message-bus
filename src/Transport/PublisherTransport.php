<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface PublisherTransport extends SubscriberTransport
{
    /**
     * @param non-empty-list<Envelope> $events
     */
    public function publish(array $events): void;
}
