<?php

declare(strict_types=1);

namespace Telephantast\MessageBus;

use Telephantast\Message\Event;
use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler\DeterministicHandler;

/**
 * @api
 */
abstract class HandlerRegistry
{
    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param class-string<TMessage> $messageClass
     * @return Handler<TResult, TMessage>
     */
    final public function get(string $messageClass): Handler
    {
        $handler = $this->find($messageClass);

        if ($handler !== null) {
            return $handler;
        }

        if (is_subclass_of($messageClass, Event::class)) {
            /** @var DeterministicHandler<TResult, TMessage> */
            return new DeterministicHandler('null event handler', null);
        }

        throw new \RuntimeException(sprintf('No handler for non-event message %s', $messageClass));
    }

    /**
     * @template TResult
     * @template TMessage of Message<TResult>
     * @param class-string<TMessage> $messageClass
     * @return ?Handler<TResult, TMessage>
     */
    abstract public function find(string $messageClass): ?Handler;
}
