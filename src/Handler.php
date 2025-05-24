<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

/**
 * @template TSupportedMessages of Message
 * @template-covariant TRequiredMessages of Message = \Thesis\Message\Event
 * @template-contravariant TTransaction of object = object
 */
interface Handler
{
    /**
     * @var non-empty-string
     */
    public string $id { get; }

    /**
     * @var non-empty-list<class-string<TSupportedMessages>>
     */
    public array $messageClasses { get; }

    /**
     * @template TResult
     * @template TMessage of TSupportedMessages&Message<TResult>
     * @param Envelope<TMessage> $envelope
     * @param Dispatcher<TRequiredMessages> $dispatcher
     * @param TTransaction $transaction
     * @return TResult
     */
    public function handle(Envelope $envelope, Dispatcher $dispatcher, object $transaction): mixed;
}
