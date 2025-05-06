<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Persistence\Transaction;
use function Typhoon\Describe\describeReflectedDeclaration;

/**
 * @api
 * @template TMessage of Message
 * @template TTransaction of Transaction = Transaction
 * @implements Handler<TMessage, TTransaction>
 */
final readonly class CallableHandler implements Handler
{
    /**
     * @var non-empty-string
     */
    public string $id;

    /**
     * @param callable(TMessage, HandlingContext<TTransaction>, Envelope<TMessage>): void $handler
     * @param ?non-empty-string $id
     */
    public function __construct(
        private mixed $handler,
        ?string $id = null,
    ) {
        $this->id = $id ?? describeReflectedDeclaration(new \ReflectionFunction($handler(...)));
    }

    public function handle(Envelope $envelope, HandlingContext $context): void
    {
        ($this->handler)($envelope->message, $context, $envelope);
    }
}
