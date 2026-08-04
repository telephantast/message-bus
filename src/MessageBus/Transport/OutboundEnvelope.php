<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Headers;
use Thesis\Time\TimeSpan;

/**
 * @api
 */
final readonly class OutboundEnvelope
{
    /**
     * @param non-empty-string $address
     */
    public function __construct(
        public Operation $operation,
        public string $address,
        public string $payload,
        public Headers $headers,
        public TimeSpan $delay = new TimeSpan(0),
        public ?TransportOptions $transportOptions = null,
    ) {}
}
