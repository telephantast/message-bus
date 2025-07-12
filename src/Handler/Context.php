<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

final class Context
{
    /**
     * @var array<class-string, object>
     */
    private array $objects = [];

    /**
     * @param class-string $class
     */
    public function has(string $class): bool
    {
        return isset($this->objects[$class]);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function get(string $class, ?string $notFoundMessage = null): object
    {
        /** @var T */
        return $this->objects[$class] ?? throw new \LogicException($notFoundMessage ?? "Value registered as `{$class}` not found");
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ?T
     */
    public function find(string $class): ?object
    {
        /** @var ?T */
        return $this->objects[$class] ?? null;
    }

    /**
     * @param class-string ...$as
     */
    public function with(object $value, string ...$as): self
    {
        $context = clone $this;

        foreach ($as ?: [$value::class] as $class) {
            \assert($value instanceof $class);
            $context->objects[$class] = $value;
        }

        return $context;
    }
}
