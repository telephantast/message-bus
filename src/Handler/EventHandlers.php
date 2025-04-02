<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Event;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\MessageContext;

/**
 * @api
 * @template TEvent of Event
 * @implements Handler<null, TEvent>
 */
final readonly class EventHandlers implements Handler
{
    /**
     * @param iterable<Handler<null, TEvent>> $handlers
     */
    public function __construct(
        private iterable $handlers,
    ) {}

    public function id(): string
    {
        $handlerIds = [];

        foreach ($this->handlers as $handler) {
            $handlerIds[] = $handler->id();
        }

        sort($handlerIds);

        /** @var non-empty-string */
        return json_encode($handlerIds);
    }

    public function handle(MessageContext $messageContext): mixed
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($messageContext);
        }

        return null;
    }
}
