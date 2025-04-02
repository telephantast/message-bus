<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

/**
 * @api
 */
final readonly class MessageClassBasedExchangeResolver implements ExchangeResolver
{
    public function resolve(string $messageClass): string
    {
        /** @var non-empty-string */
        return str_replace('\\', '.', $messageClass);
    }
}
