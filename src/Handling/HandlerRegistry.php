<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\Message\Message;

/**
 * @api
 * @template TWrappedTransaction of object = object
 */
interface HandlerRegistry
{
    /**
     * @var list<class-string<Message>>
     */
    public array $messages { get; }

    /**
     * @template TMessage of Message
     * @param class-string<TMessage> $messageClass
     * @return list<Handler<TMessage, TWrappedTransaction>>
     */
    public function getHandlers(string $messageClass): array;
}
