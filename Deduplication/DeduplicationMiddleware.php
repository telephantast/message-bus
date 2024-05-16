<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Deduplication;

use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;
use function Telephantast\MessageBus\MessageId\messageId;

/**
 * @api
 */
final readonly class DeduplicationMiddleware implements Middleware
{
    public function __construct(
        private Deduplicator $deduplicator,
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($this->deduplicator->isHandled($pipeline->id(), messageId($messageContext))) {
            return null;
        }

        return $pipeline->continue();
    }
}
