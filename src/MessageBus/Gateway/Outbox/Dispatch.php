<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Gateway\Outbox;

use Thesis\MessageBus\ConsumptionId;

/**
 * @api
 */
final readonly class Dispatch
{
    public function __construct(
        public ConsumptionId $id,
    ) {}
}
