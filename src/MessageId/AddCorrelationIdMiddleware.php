<?php

declare(strict_types=1);

namespace Thesis\MessageBus\MessageId;

use Thesis\MessageBus\MessageContext;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

/**
 * @api
 */
final readonly class AddCorrelationIdMiddleware implements Middleware
{
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasStamp(CorrelationId::class)) {
            $correlationId = $messageContext->parent?->getStamp(CorrelationId::class)?->correlationId;
            $messageContext->setStamp(new CorrelationId($correlationId ?? $messageContext->getMessageId()));
        }

        return $pipeline->continue();
    }
}
