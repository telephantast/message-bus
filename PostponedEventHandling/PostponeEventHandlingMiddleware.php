<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\PostponedEventHandling;

use Telephantast\Message\Event;
use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 */
final readonly class PostponeEventHandlingMiddleware implements Middleware
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
