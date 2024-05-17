<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Deduplication;

use Telephantast\MessageBus\Async\ConsumerQueue;
use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 */
final readonly class DeduplicateMiddleware implements Middleware
{
    public function __construct(
        private Deduplicator $deduplicator,
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $queue = $messageContext->getAttribute(ConsumerQueue::class)?->queue ?? throw new \LogicException();

        if ($this->deduplicator->isHandled($queue, $messageContext->getMessageId())) {
            return null;
        }

        return $pipeline->continue();
    }
}
