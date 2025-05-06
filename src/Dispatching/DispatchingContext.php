<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Dispatching;

use Thesis\MessageBus\Envelope;

/**
 * @api
 */
final readonly class DispatchingContext
{
    /**
     * @param non-empty-string $endpoint
     */
    public function __construct(
        public string $endpoint,
        public ?Envelope $cause = null,
    ) {}
}
