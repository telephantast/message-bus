<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Amp\Cancellation;

/**
 * @api
 *
 * @template-covariant Tx of object
 */
interface ReliableReceiver
{
    /**
     * @param non-empty-string $name
     * @param callable(Envelope, Tx): list<Envelope> $handler
     * @return callable(): void Cancel function
     */
    public function consume(string $name, callable $handler, ErrorHandler $errorHandler, Cancellation $cancellation): callable;
}
