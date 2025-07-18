<?php

declare(strict_types=1);

namespace Thesis\MessageBus\MessageMatcher;

use Thesis\MessageBus\MessageMatcher;

final readonly class AnyOf implements MessageMatcher
{
    /**
     * @param non-empty-list<class-string> $messages
     */
    public function __construct(
        private array $messages,
    ) {}

    public function matches(string $messageClass): bool
    {
        return \in_array($messageClass, $this->messages, strict: true);
    }
}
