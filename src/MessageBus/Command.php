<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Time\TimeSpan;

/**
 * @api
 *
 * @template-covariant T of object = object
 */
final readonly class Command
{
    /**
     * @template TMessage of object
     * @param TMessage|self<TMessage> $command
     * @return self<TMessage>
     */
    public static function from(object $command): self
    {
        if ($command instanceof self) {
            return $command;
        }

        return new self($command);
    }

    /**
     * @param T $payload
     * @param ?non-empty-string $replyCorrelationId
     * @param ?non-empty-string $destination
     * @param ?non-empty-string $id
     */
    public function __construct(
        public object $payload,
        public TimeSpan $delay = new TimeSpan(),
        public ?string $replyCorrelationId = null,
        public ?string $destination = null,
        public ?string $id = null,
        public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {}

    /**
     * @param non-empty-string $replyCorrelationId
     */
    public function withReplyCorrelationId(string $replyCorrelationId): static
    {
        return new self(
            payload: $this->payload,
            delay: $this->delay,
            replyCorrelationId: $replyCorrelationId,
            destination: $this->destination,
            id: $this->id,
            createdAt: $this->createdAt,
        );
    }
}
