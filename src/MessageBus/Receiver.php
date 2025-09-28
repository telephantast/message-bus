<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Amp\Cancellation;

/**
 * @api
 */
interface Receiver
{
    /**
     * @param non-empty-string $name
     * @param callable(Envelope): (Ack|Retry|Reject) $handler
     * @return \Closure(): void Cancel function
     */
    public function consume(string $name, callable $handler, Cancellation $cancellation): \Closure;
}
