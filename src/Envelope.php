<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @template-covariant TMessage of object = object
 */
final class Envelope
{
    public static function wrap(object $message): self
    {
        if ($message instanceof self) {
            return $message;
        }

        return new self($message);
    }

    /**
     * @var class-string<TMessage>
     */
    public string $messageClass { get => $this->message::class; } /** @phpstan-ignore generics.variance */

    /**
     * @param TMessage $message
     */
    public function __construct(
        public readonly object $message,
        public readonly Stamps $stamps = new Stamps(),
    ) {}
}
