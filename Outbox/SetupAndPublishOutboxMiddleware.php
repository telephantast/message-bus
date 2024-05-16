<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Outbox;

use Amp\Future;
use Telephantast\MessageBus\Async\TransportPublish;
use Telephantast\MessageBus\Envelope;
use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;
use function Amp\Future\await;
use function Telephantast\MessageBus\MessageId\messageId;

/**
 * @api
 */
final readonly class SetupAndPublishOutboxMiddleware implements Middleware
{
    public function __construct(
        private OutboxRepository $repository,
        private TransportPublish $publish,
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $outbox = $messageContext->parent?->getAttribute(Outbox::class);

        if ($outbox !== null) {
            $messageContext->setAttribute($outbox);

            return $pipeline->continue();
        }

        $outboxId = messageId($messageContext);
        $messageContext->setAttribute(new Outbox($outboxId));

        $result = $pipeline->continue();

        await(array_map(
            fn(Envelope $envelope): Future => $this->publish->publish($envelope)->map(function () use ($envelope): void {
                $this->repository->remove(messageId($envelope));
            }),
            $this->repository->get($outboxId),
        ));

        return $result;
    }
}
