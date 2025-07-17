<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\Message\Command;

final readonly class DispatchOutbox implements Command
{
    /**
     * @param non-empty-string $incomingMessageId
     */
    public function __construct(
        public string $incomingMessageId,
    ) {}
}
