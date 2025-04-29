<?php

declare(strict_types=1);

namespace Thesis\MessageBus\HandlerRegistry;

use Thesis\MessageBus\HandlerRegistry;

/**
 * @api
 */
abstract class ScopedHandlerRegistry extends HandlerRegistry
{
    abstract public function scoped(): static;
}
