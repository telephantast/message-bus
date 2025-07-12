<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Command;

interface Sender
{
    /**
     * @no-named-arguments
     * @param Command|Envelope<Command> ...$commands
     */
    public function send(Command|Envelope ...$commands): void;
}
