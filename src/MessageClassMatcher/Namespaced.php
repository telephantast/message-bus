<?php

declare(strict_types=1);

namespace Thesis\MessageBus\MessageClassMatcher;

use Thesis\MessageBus\MessageClassMatcher;

final readonly class Namespaced implements MessageClassMatcher
{
    public function __construct(
        private string $namespace,
    ) {}

    public function matches(string $messageClass): bool
    {
        return str_starts_with($messageClass, $this->namespace);
    }
}
