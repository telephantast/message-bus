<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

// todo array access
final class Stamps
{
    /**
     * @var array<non-empty-string, Stamp|\DateTimeImmutable>
     */
    private array $stamps = [];

    /**
     * @param list<Stamp|\DateTimeImmutable> $stamps
     */
    public function __construct(array $stamps = [])
    {
        foreach ($stamps as $stamp) {
            $this->stamps[$stamp::class] = $stamp;
        }
    }

    /**
     * @var list<Stamp|\DateTimeImmutable>
     */
    public array $list { get => array_values($this->stamps); }

    /**
     * @param class-string<Stamp|\DateTimeImmutable> $stamp
     */
    public function has(string $stamp): bool
    {
        return isset($this->stamps[$stamp]);
    }

    /**
     * @template TStamp of Stamp|\DateTimeImmutable
     * @param class-string<TStamp> $stamp
     * @return ?TStamp
     */
    public function find(string $stamp): null|Stamp|\DateTimeImmutable
    {
        /** @var ?TStamp */
        return $this->stamps[$stamp] ?? null;
    }

    /**
     * @no-named-arguments
     */
    public function with(Stamp|\DateTimeImmutable ...$stamps): static
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
     * @param class-string<Stamp|\DateTimeImmutable> ...$stampClasses
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
