<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\MessageId;

use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 */
final readonly class MessageIdMiddleware implements Middleware
{
    public const int RECOMMENDED_PRIORITY = 1000;

    public function __construct(
        private MessageIdGenerator $messageIdGenerator = new RandomMessageIdGenerator(),
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        if ($messageContext->hasStamp(MessageId::class)) {
            return $pipeline->continue();
        }

        $messageContext->setStamp(MessageId::fromCause(
            messageId: $this->messageIdGenerator->generate(),
            cause: $messageContext->parent?->getStamp(MessageId::class),
        ));

        return $pipeline->continue();
    }
}
