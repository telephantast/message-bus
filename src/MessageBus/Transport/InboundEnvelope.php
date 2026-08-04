<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Headers;

/**
 * @api
 */
final readonly class InboundEnvelope
{
    public function __construct(
        public string $payload,
        public Headers $headers,
    ) {}
}
