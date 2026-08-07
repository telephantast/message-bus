<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Consumption\Recoverability;

use Thesis\Time\TimeSpan;

/**
 * @api
 */
final readonly class Retry
{
    public static function immediately(): self
    {
        /** @var self */
        static $retry = new self(new TimeSpan(0));

        return $retry;
    }

    public function __construct(
        public TimeSpan $delay,
    ) {}
}
