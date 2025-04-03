<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

/**
 * @api
 * @template-covariant TResult
 * @template-covariant TMessage of Message<TResult>
 */
final class Context
{
    /**
     * @var array<class-string<ContextAttribute>, ContextAttribute>
     */
    private array $attributes = [];

    /**
     * @param Envelope<TResult, TMessage> $envelope
     * @param list<ContextAttribute> $attributes
     */
    public function __construct(
        private readonly MessageBus $messageBus,
        public readonly Envelope $envelope,
        array $attributes = [],
    ) {
        foreach ($attributes as $attribute) {
            $this->addAttribute($attribute);
        }
    }

    /**
     * @param class-string<ContextAttribute> $class
     */
    public function hasAttribute(string $class): bool
    {
        return isset($this->attributes[$class]);
    }

    /**
     * @template TAttribute of ContextAttribute
     * @param class-string<TAttribute> $class
     * @return ?TAttribute
     */
    public function getAttribute(string $class): ?ContextAttribute
    {
        /** @var ?TAttribute */
        return $this->attributes[$class] ?? null;
    }

    public function addAttribute(ContextAttribute $attribute): void
    {
        if (isset($this->attributes[$attribute::class])) {
            throw new \LogicException(\sprintf('Attribute `%s` already exists', $attribute::class));
        }

        $this->attributes[$attribute::class] = $attribute;
    }

    /**
     * @template TDispatchResult
     * @param Message<TDispatchResult> $message
     * @return TDispatchResult
     */
    public function dispatch(Message $message, PublishOptions $options = new PublishOptions()): mixed
    {
        return $this->messageBus->dispatch(
            message: $message,
            options: $options,
            causation: $this->envelope,
            attributes: array_values(
                array_filter(
                    $this->attributes,
                    static fn(ContextAttribute $attribute): bool => $attribute instanceof InheritableContextAttribute,
                ),
            ),
        );
    }
}
