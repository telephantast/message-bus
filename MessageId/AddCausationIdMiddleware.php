<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 */
final readonly class AddCausationIdMiddleware implements Middleware
{
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasStamp(CausationId::class)) {
            $causationId = $messageContext->parent?->getStamp(MessageId::class)?->messageId;

            if ($causationId !== null) {
                $messageContext->setStamp(new CausationId($causationId));
            }
        }

        return $pipeline->continue();
    }
}
