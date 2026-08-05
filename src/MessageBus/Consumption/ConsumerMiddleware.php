<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption;

use Thesis\MessageBus\Transport\ConsumerHandler;
use Thesis\MessageBus\Transport\Disposition;
use Thesis\MessageBus\Transport\InboundEnvelope;

/**
 * @api
 */
interface ConsumerMiddleware
{
    /**
     * @param non-empty-string $endpoint
     */
    public function process(string $endpoint, InboundEnvelope $envelope, ConsumerHandler $handler): Disposition;
}
