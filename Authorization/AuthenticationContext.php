<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Authorization;

/**
 * @api
 * @template TPassport of object
 */
interface AuthenticationContext
{
    /**
     * @return ?TPassport
     */
    public function passport(): ?object;
}
