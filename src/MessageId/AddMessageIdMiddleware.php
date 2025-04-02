<?php

declare(strict_types=1);

namespace Thesis\MessageBus\MessageId;

use Thesis\MessageBus\MessageContext;
use Thesis\MessageBus\Middleware;
use Thesis\MessageBus\Pipeline;

/**
 * @api
 */
final readonly class AddMessageIdMiddleware implements Middleware
{
    public function __construct(
        private MessageIdGenerator $messageIdGenerator = new RandomMessageIdGenerator(),
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if (!$messageContext->hasStamp(MessageId::class)) {
            $messageContext->setStamp(new MessageId($this->messageIdGenerator->generate()));
        }

        return $pipeline->continue();
    }
}
