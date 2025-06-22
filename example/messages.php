<?php

declare(strict_types=1);

use Thesis\MessageBus\Call;

require_once __DIR__ . '/../vendor/autoload.php';

final readonly class Ping
{
    public function __construct(
        public string $text,
    ) {}
}

final readonly class Pong
{
    public function __construct(
        public string $text,
    ) {}
}

/**
 * @implements Call<\DateTimeImmutable>
 */
final readonly class GetTimestamp implements Call {}
