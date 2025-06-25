<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Command;
use Thesis\MessageBus\Envelope;

final readonly class RoutedCommand
{
    /**
     * @param non-empty-string $endpoint
     * @param Envelope<Command> $envelope
     */
    public function __construct(
        public string $endpoint,
        public Envelope $envelope,
    ) {}
}
