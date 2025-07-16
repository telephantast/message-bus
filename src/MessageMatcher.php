<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

interface MessageMatcher
{
    /**
     * @param class-string<Message<*>> $messageClass
     */
    public function matches(string $messageClass): bool;
}
