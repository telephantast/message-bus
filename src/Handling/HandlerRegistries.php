<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

/**
 * @api
 * @template TTransaction of object = object
 */
final class HandlerRegistries implements HandlerRegistry
{
    /**
     * @param iterable<HandlerRegistry<TTransaction>> $handlerRegistries
     */
    public function __construct(
        private readonly iterable $handlerRegistries,
    ) {}

    public array $messages {
        get {
            $messagesLists = [];

            foreach ($this->handlerRegistries as $handlerRegistry) {
                $messagesLists[] = $handlerRegistry->messages;
            }

            return array_values(
                array_unique(
                    array_merge(...$messagesLists),
                ),
            );
        }
    }

    public function getHandlers(string $messageClass): array
    {
        $handlersLists = [];

        foreach ($this->handlerRegistries as $handlerRegistry) {
            $handlersLists[] = $handlerRegistry->getHandlers($messageClass);
        }

        return array_merge(...$handlersLists);
    }
}
