<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use function Typhoon\Formatter\formatFunction;

final class InMemorySyncTransport implements SyncTransport
{
    /**
     * @var array<non-empty-string, \Closure(Envelope): mixed>
     */
    private array $handlers = [];

    public function request(string $endpoint, Envelope $envelope): mixed
    {
        $handler = $this->handlers[$endpoint]
            ?? throw new \Exception(\sprintf('Endpoint `%s` is not consuming.', $endpoint));

        return $handler($envelope);
    }

    public function consume(string $endpoint, \Closure $handler): \Closure
    {
        if (isset($this->handlers[$endpoint])) {
            throw new \Exception(\sprintf(
                'Endpoint `%s` is already consumed by %s',
                $endpoint,
                formatFunction($this->handlers[$endpoint]),
            ));
        }

        $this->handlers[$endpoint] = $handler;

        return function () use ($endpoint): void {
            unset($this->handlers[$endpoint]);
        };
    }
}
