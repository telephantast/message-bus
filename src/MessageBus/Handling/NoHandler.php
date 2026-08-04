<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

use Thesis\MessageBus\MessageBusException;

/**
 * @api
 */
final class NoHandler extends \RuntimeException implements MessageBusException
{
    /**
     * @param class-string $messageClass
     */
    public function __construct(string $messageClass, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('No handler registered for message "%s".', $messageClass), previous: $previous);
    }
}
