<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Event;
use Thesis\MessageBus\Envelope;

interface EventReceiver
{
    /**
     * @param non-empty-string $endpoint
     * @param callable(Envelope<Event>): void $consumer
     */
    public function consumeEvents(string $endpoint, callable $consumer): void;
}
