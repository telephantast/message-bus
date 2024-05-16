<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler;
use Telephantast\MessageBus\MessageContext;

/**
 * @api
 * @template TMessage of Message<null>
 * @implements Handler<null, TMessage>
 */
final readonly class PublishHandler implements Handler
{
    /**
     * @param non-empty-string $id
     */
    public function __construct(
        private TransportPublish $publish,
        private string $id = 'publish',
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function handle(MessageContext $messageContext): mixed
    {
        $this->publish->publish($messageContext->envelope)->await();

        return null;
    }
}
