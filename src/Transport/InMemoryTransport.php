<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final class InMemoryTransport implements Transport
{
    /**
     * @var array<class-string<Message>, list<non-empty-string>>
     */
    private array $messageClassToEndpoints = [];

    /**
     * @var list<Envelope>
     */
    private array $inFlightEnvelopes = [];

    /**
     * @var array<non-empty-string, array<int, \Closure(Envelope): void>>
     */
    private array $handlersByEndpoint = [];

    public function setup(string $endpoint, array $localMessages): void
    {
        foreach ($localMessages as $localMessage) {
            $this->messageClassToEndpoints[$localMessage][] = $endpoint;
        }
    }

    public function publish(array $envelopes): void
    {
        $this->inFlightEnvelopes = [
            ...$this->inFlightEnvelopes,
            ...$envelopes,
        ];
        $this->deliver();
    }

    public function consume(string $endpoint, \Closure $handler): \Closure
    {
        $this->handlersByEndpoint[$endpoint][] = $handler;
        $index = array_key_last($this->handlersByEndpoint[$endpoint]);

        return function () use ($endpoint, $index): void {
            unset($this->handlersByEndpoint[$endpoint][$index]);
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
            foreach ($this->messageClassToEndpoints[$envelope->messageClass] ?? [] as $endpoint) {
                foreach ($this->handlersByEndpoint[$endpoint] ?? [] as $handler) {
                    $handler($envelope);
                }
            }
        }
    }
}
