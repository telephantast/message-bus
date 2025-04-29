<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
final class ContextAttributes
{
    /**
     * @var array<class-string<ContextAttribute>, ContextAttribute>
     */
    private array $attributes = [];

    /**
     * @param list<ContextAttribute> $attributes
     */
    public function __construct(array $attributes = [])
    {
        array_walk($attributes, $this->add(...));
    }

    /**
     * @param class-string<ContextAttribute> $class
     */
    public function has(string $class): bool
    {
        return isset($this->attributes[$class]);
    }

    /**
     * @template TAttribute of ContextAttribute
     * @param class-string<TAttribute> $class
     * @return ?TAttribute
     */
    public function get(string $class): ?ContextAttribute
    {
        /** @var ?TAttribute */
        return $this->attributes[$class] ?? null;
    }

    public function add(ContextAttribute $attribute): void
    {
        if (isset($this->attributes[$attribute::class])) {
            throw new \LogicException(\sprintf('Attribute `%s` already exists', $attribute::class));
        }

        $this->attributes[$attribute::class] = $attribute;
    }

    public function child(): self
    {
        $copy = clone $this;
        $copy->attributes = array_filter(
            $copy->attributes,
            static fn(ContextAttribute $attribute): bool => $attribute instanceof InheritableContextAttribute,
        );

        return $copy;
    }
}
