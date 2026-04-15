<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Delivery;

use Thesis\MessageBus\Envelope;

/**
 * @api
 */
interface Receiver
{
    /**
     * @param non-empty-string $name
     * @param callable(Envelope): Disposition $handler
     * @return callable(): void Cancel
     */
    public function subscribe(string $name, callable $handler): callable;
}
