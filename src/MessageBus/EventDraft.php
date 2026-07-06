<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template-covariant T of object = object
 */
final readonly class EventDraft
{
    /**
     * @template TMessage of object
     * @param TMessage|self<TMessage> $event
     * @return self<TMessage>
     */
    public static function from(object $event): self
    {
        if ($event instanceof self) {
            return $event;
        }

        return new self($event);
    }

    /**
     * @param T $payload
     * @param ?non-empty-string $id
     */
    public function __construct(
        public object $payload,
        public ?string $id = null,
        public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {}
}
