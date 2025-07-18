<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\MessageBus\Envelope;

interface CommandSender
{
    /**
     * @param non-empty-string $endpoint
     * @param non-empty-list<Envelope> $commands
     */
    public function send(string $endpoint, array $commands): void;
}
