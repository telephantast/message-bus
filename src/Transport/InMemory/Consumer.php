<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport\InMemory;

use Thesis\MessageBus\Envelope;

final class Consumer
{
    private bool $consuming = false;

    /**
     * @param \SplQueue<Envelope> $queue
     * @param callable(non-empty-list<Envelope>): void $handler
     * @param positive-int $maxBatchSize
     */
    public function __construct(
        private readonly \SplQueue $queue,
        private readonly mixed $handler,
        private readonly int $maxBatchSize,
    ) {}

    public function consume(): void
    {
        if ($this->consuming) {
            return;
        }

        $this->consuming = true;

        while (!$this->queue->isEmpty()) {
            $batch = [];

            do {
                $batch[] = $this->queue->dequeue();
            } while (\count($batch) < $this->maxBatchSize && !$this->queue->isEmpty());

            try {
                ($this->handler)($batch);
            } catch (\Throwable $exception) {
                $this->consuming = false;

                foreach (array_reverse($batch) as $envelope) {
                    $this->queue->unshift($envelope);
                }

                throw $exception;
            }
        }

        $this->consuming = false;
    }
}
