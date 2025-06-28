<?php

declare(strict_types=1);

namespace Thesis\MessageBus\MessageClassMatcher;

use Thesis\MessageBus\MessageClassMatcher;

enum Boolean implements MessageClassMatcher
{
    case True;
    case False;

    public function matches(string $messageClass): bool
    {
        return $this === self::True;
    }
}
