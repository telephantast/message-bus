<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Outbox;

use Thesis\MessageBus\MessageContext;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

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

        $outbox->add($messageContext->getEnvelope());

        /** @phpstan-ignore return.type */
        return null;
    }
}
