<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\Message\Message;

abstract class HandlersTypeChecker
{
    /**
     * @return HandlerContext<Command|Event, \RuntimeException>
     */
    abstract public function context(): HandlerContext;

    /**
     * @return Handlers<Command, \Exception>
     */
    abstract public function handlers(): Handlers;

    /**
     * @return Handler<null, Event, Message, object>
     */
    abstract public function handler(): Handler;

    final public function test(): void
    {
        $this->handlers()->handle(new class implements Command {}, $this->context());
        $this->handlers()->with($this->handler());
    }
}
