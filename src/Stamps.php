<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

// todo array access
final class Stamps
{
    /**
     * @var array<class-string, object>
     */
    private array $stamps = [];

    /**
     * @param list<object> $stamps
     */
    public function __construct(array $stamps = [])
    {
        foreach ($stamps as $stamp) {
            $this->stamps[$stamp::class] = $stamp;
        }
    }

    /**
     * @var list<object>
     */
    public array $list { get => array_values($this->stamps); }

    /**
     * @param class-string $stamp
     */
    public function has(string $stamp): bool
    {
        return isset($this->stamps[$stamp]);
    }

    /**
     * @template TStamp of object
     * @param class-string<TStamp> $stamp
     * @return ?TStamp
     */
    public function find(string $stamp): ?object
    {
        /** @var ?TStamp */
        return $this->stamps[$stamp] ?? null;
    }

    /**
     * @no-named-arguments
     */
    public function with(object ...$stamps): static
    {
        if ($stamps === []) {
            return $this;
        }

        $copy = clone $this;

        foreach ($stamps as $stamp) {
            $copy->stamps[$stamp::class] = $stamp;
        }

        return $copy;
    }

    /**
     * @no-named-arguments
     * @param class-string ...$stampClasses
     */
    public function without(string ...$stampClasses): static
    {
        if ($stampClasses === []) {
            return $this;
        }

        $copy = clone $this;

        foreach ($stampClasses as $stamp) {
            unset($copy->stamps[$stamp]);
        }

        return $copy;
    }
}
