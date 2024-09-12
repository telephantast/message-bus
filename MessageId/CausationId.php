<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

use Telephantast\MessageBus\Stamp;

/**
 * @api
 */
final class CausationId implements Stamp
{
    /**
     * @param ?non-empty-string $causationId
     */
    public function __construct(
        public readonly ?string $causationId,
    ) {}
}
