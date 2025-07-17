<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope\MessageId;

/**
 * @template-covariant TMessage of Message
 */
final class Envelope
{
    /**
     * @template TWrappedMessage of Message
     * @param TWrappedMessage|self<TWrappedMessage> $message
     * @return self<TWrappedMessage>
     */
    public static function wrap(Message|self $message): self
    {
        if ($message instanceof self) {
            return $message;
        }

        return new self($message);
    }

    /**
     * @var class-string<TMessage>
     */
    public string $messageClass { /** @phpstan-ignore generics.variance */
        get => $this->message::class;
    }

    /**
     * @var non-empty-string
     */
    public string $messageId {
        get => $this->stamps->find(MessageId::class)->messageId ?? throw new \LogicException('No message ID');
    }

    public \DateTimeImmutable $timestamp {
        get => $this->stamps->find(\DateTimeImmutable::class) ?? throw new \LogicException('No timestamp');
    }

    public Stamps $stamps;

    /**
     * @param TMessage $message
     * @param Stamps|list<Stamp> $stamps
     */
    public function __construct(
        public readonly Message $message,
        array|Stamps $stamps = new Stamps(),
    ) {
        $this->stamps = $stamps instanceof Stamps ? $stamps : new Stamps($stamps);
    }

    /**
     * @template TNewMessage of Message
     * @param TNewMessage $message
     * @return self<TNewMessage>
     */
    public function withMessage(Message $message): self
    {
        return new self($message, $this->stamps);
    }

    public function withStamps(Stamps $stamps): static
    {
        return new self($this->message, $stamps);
    }
}
