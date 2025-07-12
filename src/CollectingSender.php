<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Command;

final class CollectingSender implements Sender
{
    /**
     * @var list<Envelope<Command>>
     */
    public private(set) array $commands = [];

    public function send(Command|Envelope ...$commands): void
    {
        if ($commands === []) {
            return;
        }

        $this->commands = [
            ...$this->commands,
            ...array_map(Envelope::wrap(...), $commands),
        ];
    }

    public function clear(): void
    {
        $this->commands = [];
    }
}
