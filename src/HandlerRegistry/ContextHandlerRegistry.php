<?php

declare(strict_types=1);

namespace Thesis\MessageBus\HandlerRegistry;

use Thesis\MessageBus\InheritableContextAttribute;

/**
 * @internal
 * @psalm-internal Thesis\MessageBus
 */
final readonly class ContextHandlerRegistry implements InheritableContextAttribute
{
    public function __construct(
        public ScopedHandlerRegistry $handlerRegistry,
    ) {}
}
