<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\CreatedAt;

use Psr\Clock\ClockInterface;
use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 */
final class AddCreatedAtMiddleware implements Middleware
{
    public function __construct(
        private readonly ?ClockInterface $clock = null,
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->hasStamp(CreatedAt::class)) {
            return $pipeline->continue();
        }

        $messageContext->setStamp(new CreatedAt($this->clock?->now() ?? new \DateTimeImmutable()));

        return $pipeline->continue();
    }
}
