<?php

declare(strict_types=1);

namespace Thesis\MessageBus\MessageMatcher;

use Thesis\MessageBus\MessageMatcher;

final readonly class OrX implements MessageMatcher
{
    /**
     * @param iterable<MessageMatcher> $matchers
     */
    public function __construct(
        private iterable $matchers,
    ) {}

    public function matches(string $messageClass): bool
    {
        foreach ($this->matchers as $matcher) {
            if ($matcher->matches($messageClass)) {
                return true;
            }
        }

        return false;
    }
}
