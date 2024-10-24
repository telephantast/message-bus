<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;
use Telephantast\MessageBus\Pipeline;

/**
 * @api
 */
final class AddExchangeMiddleware implements Middleware
{
    public function __construct(
        private readonly ExchangeResolver $exchangeResolver = new MessageClassBasedExchangeResolver(),
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasStamp(Exchange::class)) {
            $messageContext->setStamp(new Exchange($this->exchangeResolver->resolve($messageContext->getMessageClass())));
        }

        return $pipeline->continue();
    }
}
