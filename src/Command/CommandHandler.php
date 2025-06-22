<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Command;

use Thesis\MessageBus\Call;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Invoker;
use Thesis\MessageBus\Result;

/**
 * @template-contravariant TCommand of object
 */
interface CommandHandler
{
    /**
     * @param Envelope<TCommand> $command
     * @param Invoker<Call> $invoker
     * @return Result<null>
     */
    public function handle(Envelope $command, Invoker $invoker): Result;
}
