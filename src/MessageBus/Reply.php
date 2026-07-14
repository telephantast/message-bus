<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template-covariant T of object = object
 */
final readonly class Reply
{
    /**
     * @template TMessage of object
     * @param TMessage|self<TMessage> $reply
     * @return self<TMessage>
     */
    public static function from(object $reply): self
    {
        if ($reply instanceof self) {
            return $reply;
        }

        return new self($reply);
    }

    /**
     * @param T $payload
     * @param ?non-empty-string $correlationId
     * @param ?non-empty-string $id
     */
    public function __construct(
        public object $payload,
        public ?string $correlationId = null,
        public ?string $id = null,
        public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {}
}
