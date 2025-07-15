<?php

declare(strict_types=1);

namespace Thesis\MessageBus\MessageMatcher;

use Thesis\MessageBus\MessageMatcher;

final readonly class Namespaced implements MessageMatcher
{
    public function __construct(
        private string $namespace,
    ) {}

    public function matches(string $messageClass): bool
    {
        return str_starts_with($messageClass, $this->namespace);
    }
}
