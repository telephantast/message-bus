<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport\InMemory;

use Revolt\EventLoop;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Transport\CallableRun;
use Thesis\MessageBus\Transport\Run;

final class Queue
{
    /**
     * @var \SplQueue<Envelope>
     */
    private readonly \SplQueue $queue;

    /**
     * @var array<non-negative-int, Consumer>
     */
    private array $consumers = [];

    public function __construct()
    {
        $this->queue = new \SplQueue();
    }

    /**
     * @param non-empty-list<Envelope> $messages
     */
    public function push(array $messages): void
    {
        foreach ($messages as $message) {
            $this->queue->push($message);
        }

        $this->deliver();
    }

    /**
     * @param callable(non-empty-list<Envelope>): void $handler
     * @param positive-int $maxBatchSize
     */
    public function startConsumer(callable $handler, int $maxBatchSize): Run
    {
        $consumer = new Consumer($this->queue, $handler, $maxBatchSize);
        $this->consumers[] = $consumer;
        $key = array_key_last($this->consumers);

        $this->deliver();

        return new CallableRun(function () use ($key): void {
            unset($this->consumers[$key]);
        });
    }

    private function deliver(): void
    {
        foreach ($this->consumers as $consumer) {
            EventLoop::queue($consumer->consume(...));
        }
    }
}
