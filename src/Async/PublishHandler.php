<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async;

use Thesis\Message\Message;
use Thesis\MessageBus\Async\Outbox\OutboxCollector;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Handler;

/**
 * @api
 * @implements Handler<null, Message<null>>
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

    public function handle(Context $context): mixed
    {
        $outbox = $context->getAttribute(OutboxCollector::class);

        if ($outbox !== null) {
            $outbox->add($context->envelope);

            return null;
        }

        $this->transportPublish->publish([$context->envelope]);

        return null;
    }
}
