<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Outbox;

use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 */
final readonly class TryPublishViaOutboxMiddleware implements Middleware
{
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $outbox = $messageContext->getAttribute(Outbox::class);

        if ($outbox === null) {
            return $pipeline->continue();
        }

        $outbox->add($messageContext->envelope);

        return null;
    }
}
