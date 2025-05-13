<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Event;
use Thesis\Message\Message;

/**
 * @template-covariant TResult = null
 * @template TMessage of Message<TResult> = Message<null>
 * @template-covariant TRequiredMessages of Message = Event
 * @template-contravariant TTransaction of object = object
 */
interface Handler
{
    /**
     * @var non-empty-string
     */
    public string $id { get; }

    /**
     * @var non-empty-list<class-string<TMessage>>
     */
    public array $messageClasses { get; }

    /**
     * @param TMessage $message
     * @param HandlerContext<TRequiredMessages, TTransaction> $context
     * @return TResult
     */
    public function handle(Message $message, HandlerContext $context): mixed;
}
