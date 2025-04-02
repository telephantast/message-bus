<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use Thesis\MessageBus\MessageContext;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

/**
 * @api
 */
final readonly class AddExchangeMiddleware implements Middleware
{
    public function __construct(
        private ExchangeResolver $exchangeResolver = new MessageClassBasedExchangeResolver(),
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasStamp(Exchange::class)) {
            $messageContext->setStamp(new Exchange($this->exchangeResolver->resolve($messageContext->getMessageClass())));
        }

        return $pipeline->continue();
    }
}
