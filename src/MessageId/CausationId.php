<?php

declare(strict_types=1);

namespace Thesis\MessageBus\MessageId;

use Thesis\MessageBus\Stamp;

/**
 * @api
 */
final readonly class CausationId implements Stamp
{
    /**
     * @param ?non-empty-string $causationId
     */
    public function __construct(
        public ?string $causationId,
    ) {}
}
