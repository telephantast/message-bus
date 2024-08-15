<?php

declare(strict_types=1);

namespace Telephantast\MessageBus;

use Telephantast\Message\Message;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 * @extends ReadonlyMessageContext<TResult, TMessage>
 */
final class MessageContext extends ReadonlyMessageContext
{
    /**
     * @param Envelope<TResult, TMessage> $envelope
     */
    protected function __construct(
        private readonly MessageBus $messageBus,
        Envelope $envelope,
        ?ReadonlyMessageContext $parent = null,
    ) {
        parent::__construct($envelope, $parent);
    }

    /**
     * @internal
     * @psalm-internal Telephantast\MessageBus
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param TTMessage|Envelope<TTResult, TTMessage> $messageOrEnvelope
     * @return self<TTResult, TTMessage>
     */
    public static function start(MessageBus $messageBus, Envelope|Message $messageOrEnvelope): self
    {
        return new self($messageBus, Envelope::wrap($messageOrEnvelope));
    }

    public function setAttribute(ContextAttribute ...$attributes): void
    {
        foreach ($attributes as $attribute) {
            $this->attributes[$attribute::class] = $attribute;
        }
    }

    /**
     * @param TMessage $message
     */
    public function setMessage(Message $message): void
    {
        $this->envelope = $this->envelope->withMessage($message);
    }

    public function setStamp(Stamp ...$stamps): void
    {
        $this->envelope = $this->envelope->withStamp(...$stamps);
    }

    /**
     * @param class-string<Stamp> ...$classes
     */
    public function removeStamp(string ...$classes): void
    {
        $this->envelope = $this->envelope->withoutStamp(...$classes);
    }

    /**
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param TTMessage|Envelope<TTResult, TTMessage> $messageOrEnvelope
     * @return TTResult
     */
    public function dispatch(Envelope|Message $messageOrEnvelope): mixed
    {
        $child = new self($this->messageBus, Envelope::wrap($messageOrEnvelope), clone $this);

        foreach ($this->attributes as $attribute) {
            if ($attribute instanceof InheritableContextAttribute) {
                $child->setAttribute($attribute);
            }
        }

        return $this->messageBus->handleContext($child);
    }
}
