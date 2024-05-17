<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

use Telephantast\MessageBus\Stamp;

/**
 * @api
 * @psalm-immutable
 */
final readonly class CorrelationId implements Stamp
{
    /**
     * @param non-empty-string $correlationId
     */
    public function __construct(
        public string $correlationId,
    ) {}
}
