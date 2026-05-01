<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\MessageBus\Route\Direct;
use Thesis\MessageBus\Route\Fanout;
use Thesis\Time\TimeSpan;

/**
 * @api
 */
final readonly class OutgoingEnvelope
{
    public function __construct(
        public Direct|Fanout $route,
        public Envelope $envelope,
        public TimeSpan $delay = new TimeSpan(),
    ) {}
}
