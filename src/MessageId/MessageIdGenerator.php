<?php

declare(strict_types=1);

namespace Thesis\MessageBus\MessageId;

/**
 * @api
 */
interface MessageIdGenerator
{
    /**
     * @return non-empty-string
     */
    public function generateMessageId(): string;
}
