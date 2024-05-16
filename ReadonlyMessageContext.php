<?php

declare(strict_types=1);

namespace Telephantast\MessageBus;

use Telephantast\Message\Message;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 */
abstract class ReadonlyMessageContext
{
    /**
     * @var array<class-string<ContextAttribute>, ContextAttribute>
     */
    protected array $attributesByClass = [];

    /**
     * @param Envelope<TResult, TMessage> $envelope
     */
    public function __construct(
        /**
         * @psalm-readonly-allow-private-mutation
         */
        public Envelope $envelope,
        public readonly ?self $parent = null,
    ) {}

    final public function root(): self
    {
        $messageContext = $this;

        while ($messageContext->parent !== null) {
            $messageContext = $messageContext->parent;
        }

        return $messageContext;
    }

    /**
     * @param class-string<ContextAttribute> $class
     */
    final public function hasAttribute(string $class): bool
    {
        return isset($this->attributesByClass[$class]);
    }

    /**
     * @template TAttribute of ContextAttribute
     * @param class-string<TAttribute> $class
     * @return ?TAttribute
     */
    final public function attribute(string $class): ?ContextAttribute
    {
        /** @var ?TAttribute */
        return $this->attributesByClass[$class] ?? null;
    }

    /**
     * @return TMessage
     */
    final public function message(): Message
    {
        return $this->envelope->message;
    }

    /**
     * @return class-string<TMessage>
     */
    final public function messageClass(): string
    {
        return $this->envelope->messageClass();
    }

    /**
     * @param class-string<Stamp> $class
     */
    final public function hasStamp(string $class): bool
    {
        return $this->envelope->hasStamp($class);
    }

    /**
     * @template TStamp of Stamp
     * @param class-string<TStamp> $class
     * @return ?TStamp
     */
    final public function stamp(string $class): ?Stamp
    {
        return $this->envelope->stamp($class);
    }
}
