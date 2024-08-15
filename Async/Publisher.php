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
final readonly class Publisher implements Handler
{
    /**
     * @param non-empty-string $id
     */
    public function __construct(
        private TransportPublish $publish,
        private string $id = self::class,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function handle(MessageContext $messageContext): mixed
    {
        $this->publish->publish([$messageContext->envelope]);

        return null;
    }
}
