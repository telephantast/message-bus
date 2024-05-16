<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

/**
 * @api
 */
interface MessageIdGenerator
{
    /**
     * @return non-empty-string
     */
    public function generate(): string;
}
