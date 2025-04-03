<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Command;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
final readonly class TestCommand implements Command
{
    public function __construct(
        public ?string $data = null,
    ) {}
}
