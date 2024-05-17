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
    protected array $attributes = [];

    protected function __construct(
        /**
         * @psalm-readonly-allow-private-mutation
         * @var Envelope<TResult, TMessage>
         */
        public Envelope $envelope,
        public readonly ?self $parent = null,
    ) {}

    /**
     * @return TMessage
     */
    final public function getMessage(): Message
    {
        return $this->envelope->message;
    }

    /**
     * @return non-empty-string
     */
    final public function getMessageId(): string
    {
        return $this->envelope->getMessageId();
    }

    /**
     * @return class-string<TMessage>
     */
    final public function getMessageClass(): string
    {
        return $this->envelope->getMessageClass();
    }

    /**
     * @param class-string<ContextAttribute> $class
     */
    final public function hasAttribute(string $class): bool
    {
        return isset($this->attributes[$class]);
    }

    /**
     * @template TAttribute of ContextAttribute
     * @param class-string<TAttribute> $class
     * @return ?TAttribute
     */
    final public function getAttribute(string $class): ?ContextAttribute
    {
        /** @var ?TAttribute */
        return $this->attributes[$class] ?? null;
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
    final public function getStamp(string $class): ?Stamp
    {
        return $this->envelope->getStamp($class);
    }
}
