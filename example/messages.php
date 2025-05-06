<?php

declare(strict_types=1);

use Thesis\Message\Command;
use Thesis\Message\Event;

require_once __DIR__ . '/../vendor/autoload.php';

final readonly class Ping implements Command
{
    public function __construct(
        public string $text,
    ) {}
}

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
final readonly class Pong implements Event
{
    public function __construct(
        public string $text,
    ) {}
}
