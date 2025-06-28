<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;

interface CommandReceiver
{
    /**
     * @param non-empty-string $endpoint
     * @param callable(Envelope<Message>): void $consumer
     */
    public function consumeCommands(string $endpoint, callable $consumer): void;
}
