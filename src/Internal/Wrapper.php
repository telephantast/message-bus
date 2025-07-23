<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Psr\Clock\ClockInterface;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Envelope\CauseId;
use Thesis\MessageBus\Envelope\ConversationId;
use Thesis\MessageBus\Envelope\EnvelopeProcessor;
use Thesis\MessageBus\Envelope\MessageId;
use Thesis\MessageBus\Envelope\MessageIdGenerator;
use Thesis\MessageBus\Envelope\RandomMessageIdGenerator;
use Thesis\MessageBus\Stamps;

/**
 * @internal
 */
final readonly class Wrapper
{
    /**
     * @param list<EnvelopeProcessor> $processors
     */
    public function __construct(
        private MessageIdGenerator $messageIdGenerator = new RandomMessageIdGenerator(),
        private ?ClockInterface $clock = null,
        private array $processors = [],
        private ?Envelope $cause = null,
    ) {}

    public function withCause(?Envelope $cause): self
    {
        return new self(
            messageIdGenerator: $this->messageIdGenerator,
            clock: $this->clock,
            processors: $this->processors,
            cause: $cause,
        );
    }

    /**
     * @template TMessage of object
     * @param TMessage|Envelope<TMessage> $message
     * @return Envelope<TMessage>
     */
    public function wrap(object $message): Envelope
    {
        if (!$message instanceof Envelope) {
            $messageId = $this->messageIdGenerator->generateMessageId();

            $envelope = new Envelope($message, new Stamps([
                $this->clock?->now() ?? new \DateTimeImmutable(),
                new MessageId($messageId),
                new CauseId($this->cause?->messageId),
                $this->cause?->stamps->find(ConversationId::class) ?? new ConversationId($this->cause->messageId ?? $messageId),
            ]));

            foreach ($this->processors as $processor) {
                $envelope = $processor->process($envelope, $this->cause);
            }

            return $envelope;
        }

        /** @var Envelope<TMessage> $message */
        $stamps = $message->stamps;
        $newStamps = [];

        if (!$stamps->has(\DateTimeImmutable::class)) {
            $newStamps[] = $this->clock?->now() ?? new \DateTimeImmutable();
        }

        $messageId = $stamps->find(MessageId::class)?->messageId;

        if ($messageId === null) {
            $messageId = $this->messageIdGenerator->generateMessageId();
            $newStamps[] = new MessageId($messageId);
        }

        if (!$stamps->has(CauseId::class)) {
            $newStamps[] = new CauseId($this->cause?->messageId);
        }

        if (!$stamps->has(ConversationId::class)) {
            $newStamps[] = $this->cause?->stamps->find(ConversationId::class) ?? new ConversationId($this->cause->messageId ?? $messageId);
        }

        $envelope = $message->withStamps($stamps->with(...$newStamps));

        foreach ($this->processors as $processor) {
            $envelope = $processor->process($envelope, $this->cause);
        }

        return $envelope;
    }
}
