<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

final readonly class Runs implements Run
{
    /**
     * @param iterable<Run> $runs
     */
    public function __construct(
        private iterable $runs = [],
    ) {}

    public function stop(): void
    {
        foreach ($this->runs as $run) {
            $run->stop();
        }
    }
}
