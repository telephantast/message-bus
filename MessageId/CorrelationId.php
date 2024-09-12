<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

use Telephantast\MessageBus\Stamp;

/**
 * @api
 */
final class CorrelationId implements Stamp
{
    /**
     * @param non-empty-string $correlationId
     */
    public function __construct(
        public readonly string $correlationId,
    ) {}
}
