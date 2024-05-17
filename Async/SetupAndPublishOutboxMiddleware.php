<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Async;

use Telephantast\MessageBus\Envelope;
use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Middleware;

/**
 * @api
 */
final readonly class SetupAndPublishOutboxMiddleware implements Middleware
{
    public function __construct(
        private OutboxRepository $outboxRepository,
        private TransportPublish $publish,
        private ExchangeResolver $exchangeResolver,
    ) {}

    public function handle(MessageContext $messageContext, Pipeline $pipeline): mixed
    {
        $outbox = $messageContext->getAttribute(OutboxAttribute::class)?->outbox;

        if ($outbox !== null) {
            return $pipeline->continue();
        }

        $outbox = $this->outboxRepository->get($messageContext->getMessageId());
        $messageContext->setAttribute(new OutboxAttribute($outbox));

        $result = $pipeline->continue();
        $envelopes = $outbox->all();

        if ($envelopes === []) {
            return $result;
        }

        $this->publish->publish(
            array_map(
                fn(Envelope $envelope): PublishableEnvelope => new PublishableEnvelope(
                    exchange: $this->exchangeResolver->resolve($envelope->getMessageClass()),
                    envelope: $envelope,
                    onSuccess: static function (Envelope $envelope) use ($outbox): void {
                        $outbox->remove($envelope->getMessageId());
                    },
                ),
                $envelopes,
            ),
        );

        return $result;
    }
}
