<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Event;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Handler;

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
        return json_encode($handlerIds, JSON_THROW_ON_ERROR);
    }

    public function handle(Context $context): mixed
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($context);
        }

        return null;
    }
}
