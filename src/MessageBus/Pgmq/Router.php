<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Pgmq;

/**
 * @api
 */
interface Router
{
    /**
     * @var list<non-empty-string>
     */
    public array $queues { get; }

    /**
     * @param class-string $class
     * @return non-empty-list<non-empty-string> Queues
     */
    public function route(string $class): array;
}
