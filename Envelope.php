<?php

declare(strict_types=1);

namespace Telephantast\MessageBus;

use Telephantast\Message\Message;
use Telephantast\MessageBus\MessageId\MessageId;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TResult
 * @template-covariant TMessage of Message<TResult>
 */
final class Envelope
{
    /**
     * @param TMessage $message
     * @param array<class-string<Stamp>, Stamp> $stamps
     */
    private function __construct(
        public readonly Message $message,
        public readonly array $stamps = [],
    ) {}

    /**
     * @template TWrappedResult
     * @template TWrappedMessage of Message<TWrappedResult>
     * @param TWrappedMessage|self<TWrappedResult, TWrappedMessage> $messageOrEnvelope
     * @return self<TWrappedResult, TWrappedMessage>
     */
    public static function wrap(Message|self $messageOrEnvelope, Stamp ...$stamps): self
    {
        if ($messageOrEnvelope instanceof Message) {
            $messageOrEnvelope = new self($messageOrEnvelope);
        }

        /** @psalm-var self<TWrappedResult, TWrappedMessage> $messageOrEnvelope */
        return $messageOrEnvelope->withStamp(...$stamps);
    }

    /**
     * @return non-empty-string
     */
    public function getMessageId(): string
    {
        return $this->getStamp(MessageId::class)?->messageId ?? throw new \RuntimeException('No message id');
    }

    /**
     * @return class-string<TMessage>
     */
    public function getMessageClass(): string
    {
        return $this->message::class;
    }

    /**
     * @param class-string<Stamp> $class
     */
    public function hasStamp(string $class): bool
    {
        return isset($this->stamps[$class]);
    }

    /**
     * @template TStamp of Stamp
     * @param class-string<TStamp> $class
     * @return ?TStamp
     */
    public function getStamp(string $class): ?Stamp
    {
        /** @var ?TStamp */
        return $this->stamps[$class] ?? null;
    }

    /**
     * @param TMessage $message
     * @return self<TResult, TMessage>
     */
    public function withMessage(Message $message): self
    {
        if ($message::class !== $this->getMessageClass()) {
            throw new \InvalidArgumentException();
        }

        return new self($message, $this->stamps);
    }

    /**
     * @return self<TResult, TMessage>
     */
    public function withStamp(Stamp ...$stamps): self
    {
        if ($stamps === []) {
            return $this;
        }

        $newStamps = $this->stamps;

        foreach ($stamps as $stamp) {
            $newStamps[$stamp::class] = $stamp;
        }

        return new self($this->message, $newStamps);
    }

    /**
     * @param class-string<Stamp> $classes
     * @return self<TResult, TMessage>
     */
    public function withoutStamp(string ...$classes): self
    {
        if ($classes === []) {
            return $this;
        }

        $newStamps = $this->stamps;

        foreach ($classes as $class) {
            unset($newStamps[$class]);
        }

        return new self($this->message, $newStamps);
    }
}
