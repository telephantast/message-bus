<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use Thesis\Message\Message;

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
