<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\Message\Message;

/**
 * @api
 */
interface ExchangeResolver
{
    /**
     * @param class-string<Message> $messageClass
     * @return non-empty-string
     */
    public function resolve(string $messageClass): string;
}
