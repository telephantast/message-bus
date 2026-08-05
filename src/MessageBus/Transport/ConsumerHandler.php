<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

/**
 * @api
 */
interface ConsumerHandler
{
    public function handle(InboundEnvelope $envelope): Disposition;
}
