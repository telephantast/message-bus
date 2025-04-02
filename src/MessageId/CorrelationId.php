<?php

declare(strict_types=1);

namespace Thesis\MessageBus\MessageId;

use Thesis\MessageBus\Stamp;

/**
 * @api
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
