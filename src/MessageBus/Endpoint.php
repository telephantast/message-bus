<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Amp\Cancellation;
use Thesis\MessageBus\Exception\NoHandlerFor;

/**
 * @template Tx of object
 */
final readonly class Endpoint
{
    /**
     * @param non-empty-string $name
     * @param Handlers<Tx> $handlers
     * @param ReliableReceiver<Tx> $receiver
     */
    public function __construct(
        public string $name,
        private Handlers $handlers,
        private ErrorHandler $errorHandler,
        private ReliableReceiver $receiver,
    ) {}

    /**
     * @return \Closure(): void
     */
    public function run(Cancellation $cancellation): \Closure
    {
        $handlers = $this->handlers;

        return $this->receiver->consume(
            name: $this->name,
            handler: static function (Envelope $envelope, object $tx) use ($handlers, $cancellation) {
                $handler = $handlers->find($envelope->class) ?? throw new NoHandlerFor($envelope->class);

                return $handler->handle($envelope, $tx, $cancellation);
            },
            errorHandler: $this->errorHandler,
            cancellation: $cancellation,
        )(...);
    }
}
