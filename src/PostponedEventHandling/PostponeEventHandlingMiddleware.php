<?php

declare(strict_types=1);

namespace Thesis\MessageBus\PostponedEventHandling;

use Thesis\Message\Event;
use Thesis\MessageBus\MessageContext;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

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
            /**
             * @psalm-suppress ArgumentTypeCoercion
             * @phpstan-ignore argument.type
             */
            $eventPipelines->add($pipeline);

            /** @phpstan-ignore return.type */
            return null;
        }

        return $pipeline->continue();
    }
}
