<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Internal;

use Thesis\MessageBus\Consumption\ConsumerMiddleware;
use Thesis\MessageBus\Transport\ConsumerHandler;
use Thesis\MessageBus\Transport\Disposition;
use Thesis\MessageBus\Transport\InboundEnvelope;

/**
 * @internal
 */
final readonly class ConsumerMiddlewareStack implements ConsumerHandler
{
    /**
     * @param non-empty-string $endpoint
     * @param list<ConsumerMiddleware> $middlewares
     */
    public static function from(string $endpoint, ConsumerHandler $handler, array $middlewares): ConsumerHandler
    {
        foreach (array_reverse($middlewares) as $middleware) {
            $handler = new self($endpoint, $handler, $middleware);
        }

        return $handler;
    }

    /**
     * @param non-empty-string $endpoint
     */
    private function __construct(
        private string $endpoint,
        private ConsumerHandler $handler,
        private ConsumerMiddleware $middleware,
    ) {}

    public function handle(InboundEnvelope $envelope): Disposition
    {
        return $this->middleware->process($this->endpoint, $envelope, $this->handler);
    }
}
