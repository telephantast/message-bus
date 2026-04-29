<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template Tx of object
 */
final readonly class Endpoint
{
    /**
     * @param non-empty-string $name
     * @param Handlers<Tx> $handlers
     * @param Gateway<Tx> $gateway
     */
    public function __construct(
        public string $name,
        private Handlers $handlers,
        private Gateway $gateway,
        private IdGenerator $idGenerator = new IdGenerator\UuidV7(),
    ) {}

    /**
     * @param class-string $messageClass
     */
    public function handles(string $messageClass): bool
    {
        return $this->handlers->has($messageClass);
    }

    public function consume(Envelope $message): void
    {
        $this->gateway->consume($this->name, $this->handle(...), $message);
    }

    public function startConsumer(): Consumer
    {
        return $this->gateway->startConsumer($this->name, $this->handle(...));
    }

    /**
     * @param Tx $transaction
     * @return list<Envelope>
     */
    private function handle(Envelope $envelope, object $transaction): array
    {
        $handler = $this->handlers->get($envelope->payload::class);

        $context = new Context(
            endpoint: $this->name,
            transaction: $transaction,
            cause: $envelope->metadata,
            idGenerator: $this->idGenerator,
        );

        ($handler)($envelope, $context);

        return $context->outgoing;
    }
}
