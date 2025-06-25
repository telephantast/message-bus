<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;
use Thesis\MessageBus\Tracing\MessageId;

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
    public string $messageClass { get => $this->message::class; } /** @phpstan-ignore generics.variance */

    /**
     * @var non-empty-string
     */
    public string $messageId {
        get => $this->stamps->find(MessageId::class)->messageId ?? throw new \LogicException('No message ID');
    }

    /**
     * @param TMessage $message
     */
    public function __construct(
        public readonly Message $message,
        public readonly Stamps $stamps = new Stamps(),
    ) {}
}
