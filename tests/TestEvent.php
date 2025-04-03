<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Event;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
final readonly class TestEvent implements Event
{
    public function __construct(
        public mixed $data = null,
    ) {}
}
