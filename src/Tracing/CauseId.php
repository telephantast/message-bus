<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Thesis\MessageBus\Stamp;

/**
 * @api
 */
final readonly class CauseId implements Stamp
{
    /**
     * @param ?non-empty-string $causeId
     */
    public function __construct(
        public ?string $causeId = null,
    ) {}
}
