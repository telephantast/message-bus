<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class CauseId
{
    /**
     * @param ?non-empty-string $causeId
     */
    public function __construct(
        public ?string $causeId = null,
    ) {}
}
