<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler;
use Telephantast\MessageBus\MessageContext;

/**
 * @api
 * @template TResult
 * @template TMessage of Message<TResult>
 * @implements Handler<TResult, TMessage>
 */
final readonly class PublishHandler implements Handler
{
    /**
     * @param non-empty-string $id
     */
    public function __construct(
        private string $id = 'publisher',
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function handle(MessageContext $messageContext): mixed
    {
        $outbox = $messageContext->getAttribute(OutboxAttribute::class)?->outbox ?? throw new \LogicException();
        $outbox->add($messageContext->envelope);

        return null;
    }
}
