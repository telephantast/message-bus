<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Command;
use Thesis\Message\Event;

abstract class HandlerContextTypeChecker
{
    /**
     * @return HandlerContext<Command|Event, \RuntimeException>
     */
    abstract public function context(): HandlerContext;

    /**
     * @param HandlerContext<Command, \Exception> $context
     */
    abstract public function handle(HandlerContext $context): void;

    final public function test(): void
    {
        $this->handle($this->context());
        $this->context()->dispatch(new class implements Command {});
    }
}
