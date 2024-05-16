<?php

declare(strict_types=1);

namespace Telephantast\MessageBus;

use Telephantast\Message\Message;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TResult
 * @template-covariant TMessage of Message<TResult>
 */
final readonly class Envelope
{
    /**
     * @param TMessage $message
     * @param array<class-string<Stamp>, Stamp> $stamps
     */
    private function __construct(
        public Message $message,
        public array $stamps = [],
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
     * @return self<TResult, TMessage>
     */
    public function withStamp(Stamp ...$stamps): self
    {
        if ($stamps === []) {
            return $this;
        }

        $stampsByClass = $this->stamps;

        foreach ($stamps as $stamp) {
            $stampsByClass[$stamp::class] = $stamp;
        }

        return new self($this->message, $stampsByClass);
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

        $stampsByClass = $this->stamps;

        foreach ($classes as $class) {
            unset($stampsByClass[$class]);
        }

        return new self($this->message, $stampsByClass);
    }
}
