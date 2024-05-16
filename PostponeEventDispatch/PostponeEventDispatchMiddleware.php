<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\PostponeEventDispatch;

use Telephantast\Message\Event;
use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 */
final readonly class PostponeEventDispatchMiddleware implements Middleware
{
    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->getMessage() instanceof Event) {
            $eventPipelines = $messageContext->root()->getAttribute(PostponedEventPipelines::class);

            if ($eventPipelines === null) {
                return $pipeline->continue();
            }

            $eventPipelines->add($pipeline);

            /** @phpstan-ignore return.type */
            return null;
        }

        if ($messageContext->parent === null) {
            $eventPipelines = new PostponedEventPipelines();
            $messageContext->setAttribute($eventPipelines);
            $result = $pipeline->continue();
            $eventPipelines->continue();

            return $result;
        }

        return $pipeline->continue();
    }
}
