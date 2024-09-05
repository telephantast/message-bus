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
final class Publisher implements Handler
{
    /**
     * @param non-empty-string $id
     */
    public function __construct(
        private readonly TransportPublish $transportPublish,
        private readonly string $id = self::class,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function handle(MessageContext $messageContext): mixed
    {
        $this->transportPublish->publish([$messageContext->envelope]);

        return null;
    }
}
