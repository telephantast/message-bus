<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Outbox;

use Telephantast\MessageBus\ContextAttribute;

/**
 * @internal
 * @psalm-internal Telephantast\MessageBus\Outbox
 */
final readonly class Outbox implements ContextAttribute
{
    /**
     * @param non-empty-string $outboxId
     */
    public function __construct(
        public string $outboxId,
    ) {}
}
