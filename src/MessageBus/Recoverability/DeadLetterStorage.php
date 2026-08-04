<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Recoverability;

use Thesis\MessageBus\Transport\InboundEnvelope;

/**
 * @api
 */
interface DeadLetterStorage
{
    public function store(InboundEnvelope $envelope, FailureContext $context): void;
}
