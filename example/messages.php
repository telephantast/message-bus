<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Example;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\MessageBus\Call;

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
 * @implements Call<\DateTimeImmutable>
 */
final readonly class GetTimestamp implements Call {}
