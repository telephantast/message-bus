<?php

declare(strict_types=1);

namespace Thesis\MessageBus\HandlerRegistry;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final readonly class ArrayContainer implements ContainerInterface
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(
        private array $items = [],
    ) {}

    public function get(string $id): mixed
    {
        return $this->items[$id]
            ?? throw new class (\sprintf('Item with key "%s" not found.', $id)) extends \RuntimeException implements NotFoundExceptionInterface {};
    }

    public function has(string $id): bool
    {
        return \array_key_exists($id, $this->items);
    }
}
