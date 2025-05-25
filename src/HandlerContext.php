<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

/**
 * @template-contravariant TSupportedMessages of Message = \Thesis\Message\Event
 * @template-covariant TTransaction of object = object
 * @extends Dispatcher<TSupportedMessages>
 */
abstract class HandlerContext extends Dispatcher
{
    /**
     * @var TTransaction
     */
    abstract public object $transaction { get; } /** @phpstan-ignore generics.variance */
}
