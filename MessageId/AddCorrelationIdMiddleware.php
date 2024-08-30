<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 */
final class AddCorrelationIdMiddleware implements Middleware
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
