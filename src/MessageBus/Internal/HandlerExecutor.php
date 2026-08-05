<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\Headers;
use Thesis\MessageBus\Handling\HandlerRegistry;
use Thesis\MessageBus\Handling\NoHandler;
use Thesis\MessageBus\Handling\TransactionScope;
use Thesis\MessageBus\Transport\Dispatcher;
use Thesis\MessageBus\Transport\OutboundEnvelope;

/**
 * @internal
 *
 * @template-contravariant Tx of object
 */
final readonly class HandlerExecutor
{
    /**
     * @param non-empty-string $endpoint
     * @param HandlerRegistry<Tx> $handlerRegistry
     */
    public function __construct(
        private string $endpoint,
        private HandlerRegistry $handlerRegistry,
        private OutboundEnvelopeFactory $outboundEnvelopeFactory,
        private Dispatcher $dispatcher,
    ) {}

    /**
     * @param TransactionScope<Tx> $txScope
     * @return list<OutboundEnvelope>
     */
    public function execute(object $message, Headers $headers, TransactionScope $txScope): array
    {
        $handler = $this->handlerRegistry->handlerFor($message::class)
            ?? throw new NoHandler($message::class);

        $context = new RuntimeHandlerContext(
            endpoint: $this->endpoint,
            headers: $headers,
            envelopeFactory: $this->outboundEnvelopeFactory,
            dispatcher: $this->dispatcher,
        );

        try {
            $handler($message, $context, $txScope);
        } finally {
            $context->disableDispatch();
        }

        return $context->outboundEnvelopes;
    }
}
