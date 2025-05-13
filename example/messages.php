<?php

declare(strict_types=1);

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\Message\Message;

require_once __DIR__ . '/../vendor/autoload.php';

final readonly class Ping implements Command
{
    public function __construct(
        public string $text,
    ) {}
}

final readonly class Pong implements Event
{
    public function __construct(
        public string $text,
    ) {}
}

/**
 * @implements Message<\DateTimeImmutable>
 */
final readonly class Now implements Message {}
