<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Pgmq\Router;

use Thesis\MessageBus\Pgmq\Router;

/**
 * @api
 */
final class Map implements Router
{
    /**
     * @var array<class-string, non-empty-list<non-empty-string>>
     */
    private array $map;

    /**
     * @param array<class-string, non-empty-string|non-empty-list<non-empty-string>> $map
     */
    public function __construct(array $map)
    {
        $this->map = array_map(
            static fn(string|array $queues) => (array) $queues,
            $map,
        );
    }

    public array $queues {
        get => array_unique(array_merge(...array_values($this->map)));
    }

    public function route(string $class): array
    {
        return $this->map[$class] ?? throw new \LogicException('Not routed');
    }
}
