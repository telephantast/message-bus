<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport\InMemory;

use Thesis\MessageBus\Envelope;

final class Consumer
{
    private bool $consuming = false;

    /**
     * @param \SplQueue<Envelope> $queue
     * @param callable(Envelope): void $handler
     */
    public function __construct(
        private readonly \SplQueue $queue,
        private readonly mixed $handler,
    ) {}

    public function consume(): void
    {
        if ($this->consuming) {
            return;
        }

        $this->consuming = true;

        while (!$this->queue->isEmpty()) {
            $envelope = $this->queue->dequeue();

            try {
                ($this->handler)($envelope);
            } catch (\Throwable $exception) {
                $this->consuming = false;
                $this->queue->unshift($envelope);

                throw $exception;
            }
        }

        $this->consuming = false;
    }
}
