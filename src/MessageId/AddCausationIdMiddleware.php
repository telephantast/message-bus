<?php

declare(strict_types=1);

namespace Thesis\MessageBus\MessageId;

use Thesis\MessageBus\MessageContext;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

/**
 * @api
 */
final readonly class AddCausationIdMiddleware implements Middleware
{
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasStamp(CausationId::class)) {
            $causationId = $messageContext->parent?->getStamp(MessageId::class)?->messageId;
            $messageContext->setStamp(new CausationId($causationId));
        }

        return $pipeline->continue();
    }
}
