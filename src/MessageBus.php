<?php

declare(strict_types=1);

namespace Thesis;

use Amp\Cancellation;
use Amp\NullCancellation;
use Thesis\MessageBus\Dispatch;
use Thesis\MessageBus\Dispatcher;
use Thesis\MessageBus\Endpoint;

/**
 * @api
 */
final readonly class MessageBus
{
    /**
     * @var array<non-empty-string, Endpoint<*>>
     */
    private array $endpoints;

    /**
     * @param list<Endpoint<*>> $endpoints
     */
    public function __construct(
        private Dispatcher $dispatcher,
        array $endpoints = [],
    ) {
        $this->endpoints = array_column($endpoints, null, 'name');
    }

    /**
     * @param non-empty-string $source
     */
    public function dispatch(Dispatch $dispatch, string $source = 'message_bus'): void
    {
        $envelopes = $dispatch->seal(source: $source);

        if ($envelopes !== []) {
            $this->dispatcher->dispatch($envelopes);
        }
    }

    /**
     * @param non-empty-string|non-empty-list<non-empty-string> $endpoints
     * @return \Closure(): void
     */
    public function consume(string|array $endpoints, Cancellation $cancellation = new NullCancellation()): \Closure
    {
        $cancels = array_map(
            fn(string $name) => $this->endpoint($name)->run($cancellation),
            (array) $endpoints,
        );

        return static function () use ($cancels): void {
            foreach ($cancels as $cancel) {
                $cancel();
            }
        };
    }

    /**
     * @param non-empty-string $name
     * @return Endpoint<*>
     */
    private function endpoint(string $name): Endpoint
    {
        return $this->endpoints[$name] ?? throw new \LogicException("No endpoint `{$name}`");
    }
}
