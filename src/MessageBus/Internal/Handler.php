<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handlers;
use Thesis\MessageBus\IdGenerator;

/**
 * @internal
 *
 * @template-contravariant Tx of object
 */
final readonly class Handler
{
    /**
     * @param non-empty-string $name
     * @param Handlers<Tx> $handlers
     */
    public function __construct(
        private string $name,
        private Handlers $handlers,
        private IdGenerator $idGenerator,
    ) {}

    /**
     * @param Tx $transaction
     * @return list<Envelope>
     */
    public function __invoke(Envelope $envelope, object $transaction): array
    {
        $handler = $this->handlers->get($this->name, $envelope->payload::class);

        $context = new Context(
            consumer: $this->name,
            transaction: $transaction,
            cause: $envelope->metadata,
            idGenerator: $this->idGenerator,
        );

        ($handler)($envelope, $context);

        return $context->outgoing;
    }
}
