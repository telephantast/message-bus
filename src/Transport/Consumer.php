<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Envelope;

interface Consumer
{
    /**
     * @param non-empty-string $endpoint
     * @param callable(Envelope<Command|Event>): void $handler
     */
    public function consume(string $endpoint, callable $handler): void;
}
