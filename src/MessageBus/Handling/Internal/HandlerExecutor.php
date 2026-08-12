<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling\Internal;

use Thesis\Headers;
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
     * @param HandlerRouter<Tx> $router
     */
    public function __construct(
        private string $endpoint,
        private HandlerRouter $router,
        private OutboundEnvelopeFactory $outboundEnvelopeFactory,
        private Dispatcher $dispatcher,
    ) {}

    /**
     * @param Tx $transaction
     * @return list<OutboundEnvelope>
     */
    public function execute(object $message, Headers $headers, object $transaction): array
    {
        $handler = $this->router->handlerFor($message::class, $headers);

        $context = new RuntimeHandlerContext(
            endpoint: $this->endpoint,
            headers: $headers,
            envelopeFactory: $this->outboundEnvelopeFactory,
            dispatcher: $this->dispatcher,
        );

        try {
            $handler($message, $context, $transaction);
        } finally {
            $context->disableDispatch();
        }

        return $context->outboundEnvelopes;
    }
}
