<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption;

use Thesis\MessageBus\MessageBusException;

/**
 * @internal
 */
final class OutboxNotFound extends \RuntimeException implements MessageBusException
{
    public function __construct(ProcessingId $id, ?\Throwable $previous = null)
    {
        parent::__construct(
            \sprintf('Outbox "%s" for endpoint "%s" was not found yet.', $id->messageId, $id->endpoint),
            previous: $previous,
        );
    }
}
