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
final readonly class EnvelopeFactory
{
    /**
     * @param list<EnvelopeProcessor> $processors
     */
    public function __construct(
        private array $processors = [],
        private MessageIdGenerator $messageIdGenerator = new RandomMessageIdGenerator(),
        private ?ClockInterface $clock = null,
    ) {}

    /**
     * @template TMessage of object
     * @param non-empty-string $endpoint
     * @param TMessage|Envelope<TMessage> $message
     * @param ?Envelope<*> $cause
     * @return Envelope<TMessage>
     */
    public function create(string $endpoint, object $message, ?Envelope $cause = null): Envelope
    {
        if (!$message instanceof Envelope) {
            $messageId = $this->messageIdGenerator->generateMessageId();

            $envelope = new Envelope($message, new Stamps([
                $this->clock?->now() ?? new \DateTimeImmutable(),
                new MessageId($messageId),
                new CauseId($cause?->messageId),
                $cause?->stamps->find(ConversationId::class) ?? new ConversationId($cause->messageId ?? $messageId),
            ]));

            foreach ($this->processors as $processor) {
                $envelope = $processor->process($endpoint, $envelope, $cause);
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
            $newStamps[] = new CauseId($cause?->messageId);
        }

        if (!$stamps->has(ConversationId::class)) {
            $newStamps[] = $cause?->stamps->find(ConversationId::class) ?? new ConversationId($cause->messageId ?? $messageId);
        }

        $envelope = $message->withStamps($stamps->with(...$newStamps));

        foreach ($this->processors as $processor) {
            $envelope = $processor->process($endpoint, $envelope, $cause);
        }

        return $envelope;
    }
}
