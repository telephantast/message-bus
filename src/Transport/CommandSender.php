<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Command;
use Thesis\MessageBus\Envelope;

interface CommandSender
{
    /**
     * @param non-empty-string $toEndpoint
     * @param non-empty-list<Envelope<Command>> $commands
     */
    public function send(string $toEndpoint, array $commands): void;
}
