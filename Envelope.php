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
     * @param array<class-string<Stamp>, Stamp> $stampsByClass
     */
    private function __construct(
        public Message $message,
        public array $stampsByClass = [],
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

        /**
         * @psalm-var self<TWrappedResult, TWrappedMessage> $messageOrEnvelope
         * @phpstan-ignore varTag.differentVariable
         */
        foreach ($stamps as $stamp) {
            $messageOrEnvelope = $messageOrEnvelope->withStamp($stamp);
        }

        return $messageOrEnvelope;
    }

    /**
     * @return class-string<TMessage>
     */
    public function messageClass(): string
    {
        return $this->message::class;
    }

    /**
     * @param class-string<Stamp> $class
     */
    public function hasStamp(string $class): bool
    {
        return isset($this->stampsByClass[$class]);
    }

    /**
     * @template TStamp of Stamp
     * @param class-string<TStamp> $class
     * @return ?TStamp
     */
    public function stamp(string $class): ?Stamp
    {
        /** @var ?TStamp */
        return $this->stampsByClass[$class] ?? null;
    }

    /**
     * @return self<TResult, TMessage>
     */
    public function withStamp(Stamp $stamp): self
    {
        $stampsByClass = $this->stampsByClass;
        $stampsByClass[$stamp::class] = $stamp;

        /** @phpstan-ignore return.type */
        return new self($this->message, $stampsByClass);
    }

    /**
     * @param class-string<Stamp> $class
     * @return self<TResult, TMessage>
     */
    public function withoutStamp(string $class): self
    {
        $stampsByClass = $this->stampsByClass;
        unset($stampsByClass[$class]);

        /** @phpstan-ignore return.type */
        return new self($this->message, $stampsByClass);
    }
}
