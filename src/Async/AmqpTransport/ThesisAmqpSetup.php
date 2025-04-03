<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\AmqpTransport;

use Thesis\Amqp\Client;
use Thesis\MessageBus\Async\TransportSetup;

/**
 * @api
 */
final readonly class ThesisAmqpSetup implements TransportSetup
{
    public function __construct(
        private Client $client,
        private RoutingTopology $routingTopology = new ConventionalRoutingTopology(),
    ) {}

    /**
     * @throws \Throwable
     */
    public function setup(array $messageClassToQueues): void
    {
        $channel = $this->client->channel();

        foreach ($messageClassToQueues as $messageClass => $queues) {
            $exchange = $this->routingTopology->resolveExchange($messageClass);
            $channel->exchangeDeclare($exchange, exchangeType: 'fanout', durable: true);

            foreach ($queues as $queue) {
                $channel->queueDeclare($queue, durable: true);
                $channel->queueBind($queue, $exchange);
            }
        }

        $channel->close();
    }
}
