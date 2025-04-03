<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\AmqpTransport;

use Thesis\Amqp\DeliveryMode;
use Thesis\MessageBus\Async\TransportOptions;
use Thesis\MessageBus\Time\TimeSpan;

/**
 * @api
 */
final class AmqpOptions implements TransportOptions
{
    /**
     * @param ?int<0, 9> $priority
     */
    public function __construct(
        public DeliveryMode $deliveryMode = DeliveryMode::Persistent,
        public bool $immediate = false,
        public ?TimeSpan $expiration = null,
        public ?int $priority = null,
    ) {}
}
