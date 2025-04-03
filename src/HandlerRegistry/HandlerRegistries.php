<?php

declare(strict_types=1);

namespace Thesis\MessageBus\HandlerRegistry;

use Thesis\Message\Message;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\HandlerRegistry;

/**
 * @api
 */
final class HandlerRegistries extends HandlerRegistry
{
    /**
     * @param iterable<HandlerRegistry> $registries
     */
    public function __construct(
        private readonly iterable $registries,
    ) {}

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param class-string<TMessage> $messageClass
     * @return ?Handler<TResult, TMessage>
     */
    public function find(string $messageClass): ?Handler
    {
        foreach ($this->registries as $registry) {
            /** @var ?Handler<TResult, TMessage> $handler */
            $handler = $registry->find($messageClass);

            if ($handler !== null) {
                return $handler;
            }
        }

        return null;
    }
}
