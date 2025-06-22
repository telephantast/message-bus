<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Command;

use Thesis\MessageBus\Call;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Invoker;
use Thesis\MessageBus\Result;
use Thesis\MessageBus\Stamps;

/**
 * @template-contravariant TCommand of object
 * @implements CommandHandler<TCommand>
 */
final readonly class CallableCommandHandler implements CommandHandler
{
    /**
     * @param callable(TCommand, Invoker<Call>, Stamps): (void|null|Result<null>) $handler
     */
    public function __construct(
        private mixed $handler,
    ) {}

    public function handle(Envelope $command, Invoker $invoker): Result
    {
        $result = ($this->handler)($command->message, $invoker, $command->stamps);

        if ($result instanceof Result) {
            return $result;
        }

        return new Result();
    }
}
