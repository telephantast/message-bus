<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\PostponedEventHandling;

use Telephantast\Message\Event;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;
use Telephantast\MessageBus\Pipeline;

/**
 * @api
 */
final class PostponeEventHandlingMiddleware implements Middleware
{
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $eventPipelines = $messageContext->getAttribute(PostponedEventPipelines::class);

        if ($eventPipelines === null) {
            $eventPipelines = new PostponedEventPipelines();
            $messageContext->setAttribute($eventPipelines);
            $result = $pipeline->continue();
            $eventPipelines->continue();

            return $result;
        }

        if ($messageContext->getMessage() instanceof Event) {
            $eventPipelines->add($pipeline);

            return null;
        }

        return $pipeline->continue();
    }
}
