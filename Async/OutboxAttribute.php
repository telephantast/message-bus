<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\InheritableContextAttribute;

/**
 * @internal
 * @psalm-internal Telephantast\MessageBus\Async
 */
final readonly class OutboxAttribute implements InheritableContextAttribute
{
    public function __construct(
        public Outbox $outbox,
    ) {}
}
