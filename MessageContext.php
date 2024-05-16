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
        parent::__construct(Envelope::wrap($messageOrEnvelope), $parent);
    }

    public function setAttribute(ContextAttribute $attribute): void
    {
        $this->attributesByClass[$attribute::class] = $attribute;
    }

    public function setStamp(Stamp $stamp): void
    {
        $this->envelope = $this->envelope->withStamp($stamp);
    }

    /**
     * @param class-string<Stamp> $class
     */
    public function removeStamp(string $class): void
    {
        $this->envelope = $this->envelope->withoutStamp($class);
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
