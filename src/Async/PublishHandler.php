<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use Thesis\Message\Message;
use Thesis\MessageBus\Handler;
use Thesis\MessageBus\MessageContext;

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
        private TransportPublish $transportPublish,
        private string $id = self::class,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function handle(MessageContext $messageContext): mixed
    {
        $this->transportPublish->publish([$messageContext->getEnvelope()]);

        return null;
    }
}
