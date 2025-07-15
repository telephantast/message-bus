<?php

declare(strict_types=1);

namespace Thesis\MessageBus\MessageMatcher;

use Thesis\MessageBus\MessageMatcher;

enum Boolean implements MessageMatcher
{
    case True;
    case False;

    public function matches(string $messageClass): bool
    {
        return $this === self::True;
    }
}
