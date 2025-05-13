<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

final class Stamps
{
    /**
     * @var array<non-empty-string, Stamp>
     */
    private array $stamps = [];

    /**
     * @param list<Stamp> $stamps
     */
    public function __construct(array $stamps = [])
    {
        foreach ($stamps as $stamp) {
            $this->stamps[$stamp::class] = $stamp;
        }
    }

    /**
     * @var list<Stamp>
     */
    public array $list { get => array_values($this->stamps); }

    /**
     * @param class-string<Stamp> $stamp
     */
    public function has(string $stamp): bool
    {
        return isset($this->stamps[$stamp]);
    }

    /**
     * @template TStamp of Stamp
     * @param class-string<TStamp> $stamp
     * @return ?TStamp
     */
    public function find(string $stamp): ?Stamp
    {
        /** @var ?TStamp */
        return $this->stamps[$stamp] ?? null;
    }

    /**
     * @no-named-arguments
     */
    public function with(Stamp ...$stamps): static
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
     * @param class-string<Stamp> ...$stampClasses
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
