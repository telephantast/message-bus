<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

interface Sender
{
    /**
     * @param non-empty-list<RoutedCommand> $routedCommands
     */
    public function send(array $routedCommands): void;
}
