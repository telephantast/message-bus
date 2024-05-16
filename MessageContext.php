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
     * @internal
     * @psalm-internal Telephantast\MessageBus
     * @param TMessage|Envelope<TResult, TMessage> $messageOrEnvelope
     */
    public function __construct(
        private readonly MessageBus $messageBus,
        Envelope|Message $messageOrEnvelope,
        ?ReadonlyMessageContext $parent = null,
    ) {
        parent::__construct($messageOrEnvelope, $parent);
    }

    public function setAttribute(ContextAttribute ...$attributes): void
    {
        foreach ($attributes as $attribute) {
            $this->attributesByClass[$attribute::class] = $attribute;
        }
    }

    public function setStamp(Stamp ...$stamps): void
    {
        /** @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass */
        $this->envelope = $this->envelope->withStamp(...$stamps);
    }

    /**
     * @param class-string<Stamp> ...$classes
     */
    public function removeStamp(string ...$classes): void
    {
        /** @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass */
        $this->envelope = $this->envelope->withoutStamp(...$classes);
    }

    /**
     * @template TTResult
     * @template TTMessage of Message<TTResult>
     * @param TTMessage|Envelope<TTResult, TTMessage> $messageOrEnvelope
     * @return TTResult
     */
    public function dispatch(Envelope|Message $messageOrEnvelope, ContextAttribute ...$attributes): mixed
    {
        $childContext = new self($this->messageBus, $messageOrEnvelope, clone $this);

        foreach ($attributes as $attribute) {
            $childContext->setAttribute($attribute);
        }

        return $this->messageBus->handleContext($childContext);
    }
}
