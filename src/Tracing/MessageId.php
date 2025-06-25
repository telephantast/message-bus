<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Tracing;

use Thesis\MessageBus\Stamp;

/**
 * @api
 */
final readonly class MessageId implements Stamp
{
    /**
     * @param non-empty-string $messageId
     */
    public function __construct(
        public string $messageId,
    ) {}
}
