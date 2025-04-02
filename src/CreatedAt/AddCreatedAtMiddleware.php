<?php

declare(strict_types=1);

namespace Thesis\MessageBus\CreatedAt;

use Psr\Clock\ClockInterface;
use Thesis\MessageBus\MessageContext;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

/**
 * @api
 */
final readonly class AddCreatedAtMiddleware implements Middleware
{
    public function __construct(
        private ClockInterface $clock = new WallClock(),
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasStamp(CreatedAt::class)) {
            $messageContext->setStamp(new CreatedAt($this->clock->now()));
        }

        return $pipeline->continue();
    }
}
