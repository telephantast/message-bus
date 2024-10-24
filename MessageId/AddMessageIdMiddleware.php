<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;
use Telephantast\MessageBus\Pipeline;

/**
 * @api
 */
final class AddMessageIdMiddleware implements Middleware
{
    public function __construct(
        private readonly MessageIdGenerator $messageIdGenerator = new RandomMessageIdGenerator(),
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasStamp(MessageId::class)) {
            $messageContext->setStamp(new MessageId($this->messageIdGenerator->generate()));
        }

        return $pipeline->continue();
    }
}
