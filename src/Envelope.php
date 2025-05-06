<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Tracing\MessageId;

/**
 * @api
 * @template-covariant TMessage of Message = Message
 */
final class Envelope
{
    /**
     * @template TWrapMessage of Message
     * @param TWrapMessage|self<TWrapMessage> $message
     * @param list<Stamp> $stamps
     * @return self<TWrapMessage>
     */
    public static function wrap(Message|self $message, array $stamps = []): self
    {
        if ($message instanceof Message) {
            return new self($message, $stamps);
        }

        return $message->withStamps($stamps);
    }

    /**
     * @var array<non-empty-string, Stamp>
     */
    private array $stampByClass = [];

    /**
     * @param TMessage $message
     * @param list<Stamp> $stamps
     */
    public function __construct(
        public private(set) Message $message,
        array $stamps = [],
    ) {
        foreach ($stamps as $stamp) {
            $this->stampByClass[$stamp::class] = $stamp;
        }
    }

    /**
     * @var class-string<TMessage>
     */
    public string $messageClass { get => $this->message::class; }

    public bool $isCommand { get => $this->message instanceof Command; }

    public bool $isEvent { get => $this->message instanceof Event; }

    /**
     * @var non-empty-string
     */
    public string $messageId { get => $this->findStamp(MessageId::class)->messageId ?? throw new \Exception(); }

    /**
     * @var list<Stamp>
     */
    public array $stamps { get => array_values($this->stampByClass); }

    /**
     * @template TNewMessage of Message
     * @param TNewMessage $message
     * @return self<TNewMessage>
     */
    public function withMessage(Message $message): self
    {
        $envelope = clone $this;
        /** @phpstan-ignore assign.propertyType */
        $envelope->message = $message;

        return $envelope;
    }

    /**
     * @param class-string<Stamp> $stamp
     */
    public function hasStamp(string $stamp): bool
    {
        return isset($this->stampByClass[$stamp]);
    }

    /**
     * @template TStamp of Stamp
     * @param class-string<TStamp> $stamp
     * @return ?TStamp
     */
    public function findStamp(string $stamp): ?Stamp
    {
        /** @var ?TStamp */
        return $this->stampByClass[$stamp] ?? null;
    }

    public function withStamp(Stamp $stamp): static
    {
        $envelope = clone $this;
        $envelope->stampByClass[$stamp::class] = $stamp;

        return $envelope;
    }

    /**
     * @param list<Stamp> $stamps
     */
    public function withStamps(array $stamps): static
    {
        if ($stamps === []) {
            return $this;
        }

        $envelope = clone $this;

        foreach ($stamps as $stamp) {
            $envelope->stampByClass[$stamp::class] = $stamp;
        }

        return $envelope;
    }

    /**
     * @param class-string<Stamp> $stamp
     */
    public function withoutStamp(string $stamp): static
    {
        $envelope = clone $this;
        unset($envelope->stampByClass[$stamp]);

        return $envelope;
    }

    /**
     * @param list<class-string<Stamp>> $stamps
     */
    public function withoutStamps(array $stamps): static
    {
        if ($stamps === []) {
            return $this;
        }

        $envelope = clone $this;

        foreach ($stamps as $stamp) {
            unset($envelope->stampByClass[$stamp]);
        }

        return $envelope;
    }
}
