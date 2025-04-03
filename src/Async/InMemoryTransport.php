<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final class InMemoryTransport implements TransportSetup, TransportPublish, TransportConsume
{
    /**
     * @var array<class-string<Message>, list<non-empty-string>>
     */
    private array $messageClassToQueues = [];

    /**
     * @var list<Envelope>
     */
    private array $inFlightEnvelopes = [];

    /**
     * @var array<non-empty-string, array<int, Consumer>>
     */
    private array $consumersByQueue = [];

    public function setup(array $messageClassToQueues): void
    {
        $this->messageClassToQueues = $messageClassToQueues;
    }

    public function publish(array $envelopes): void
    {
        $this->inFlightEnvelopes = [
            ...$this->inFlightEnvelopes,
            ...$envelopes,
        ];
    }

    public function consume(Consumer $consumer): \Closure
    {
        $queue = $consumer->queue;
        $this->consumersByQueue[$queue][] = $consumer;
        $index = array_key_last($this->consumersByQueue[$queue]);

        return function () use ($queue, $index): void {
            unset($this->consumersByQueue[$queue][$index]);
        };
    }

    /**
     * @return list<Envelope>
     */
    public function inFlightEnvelopes(): array
    {
        return $this->inFlightEnvelopes;
    }

    public function deliver(): void
    {
        while ($envelope = array_shift($this->inFlightEnvelopes)) {
            foreach ($this->messageClassToQueues[$envelope->message::class] ?? [] as $queue) {
                foreach ($this->consumersByQueue[$queue] ?? [] as $consumer) {
                    $consumer->consume($envelope);
                }
            }
        }
    }
}
