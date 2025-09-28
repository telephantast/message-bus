<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 */
interface ErrorHandler
{
    public function handle(\Throwable $error, Envelope $envelope): Ack|Reject|Retry;
}
