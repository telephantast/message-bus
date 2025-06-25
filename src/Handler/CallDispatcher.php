<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\MessageBus\Call;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\Result;
use Thesis\MessageBus\Transport\Client;
use Thesis\MessageBus\Transport\Router;

/**
 * @internal
 * @psalm-internal Thesis\MessageBus
 * @implements Handler<Call<*>>
 */
final readonly class CallDispatcher implements Handler
{
    /**
     * @param array<non-empty-string, Handler<*>> $endpoints
     */
    public function __construct(
        private array $endpoints,
        private Router $router,
        private Client $client,
    ) {}

    public function handle(Envelope $envelope, Context $context): Result
    {
        $endpoint = $this->router->route($envelope)
            ?? throw new \LogicException(\sprintf('Failed to route `%s`', $envelope->messageClass));

        if (isset($this->endpoints[$endpoint])) {
            /** @phpstan-ignore return.type, argument.type */
            return $this->endpoints[$endpoint]->handle($envelope, $context->with(new Endpoint($endpoint)));
        }

        /** @phpstan-ignore return.type, argument.type */
        return new Result($this->client->invoke($endpoint, $envelope));
    }
}
