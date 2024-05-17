<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

use Telephantast\MessageBus\Stamp;

/**
 * @api
 * @psalm-immutable
 */
final readonly class CausationId implements Stamp
{
    /**
     * @param non-empty-string $causationId
     */
    public function __construct(
        public string $causationId,
    ) {}
}
