<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport\Router;

use Thesis\Message\Command;
use Thesis\MessageBus\Call;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Transport\Router;

final readonly class Map implements Router
{
    /**
     * @param array<class-string<Command|Call<*>>, non-empty-string> $map
     */
    public function __construct(
        private array $map = [],
    ) {}

    public function route(Envelope $envelope): ?string
    {
        return $this->map[$envelope->messageClass] ?? null;
    }
}
