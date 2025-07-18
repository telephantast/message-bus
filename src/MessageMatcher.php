<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @todo rename to class matcher?
 */
interface MessageMatcher
{
    /**
     * @param class-string $messageClass
     */
    public function matches(string $messageClass): bool;
}
