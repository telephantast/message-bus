<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling\Internal;

use Thesis\Headers;
use Thesis\MessageBus\HandlerContext;
use Thesis\MessageBus\Publish;
use Thesis\MessageBus\Reply;
use Thesis\MessageBus\Send;
use Thesis\MessageBus\Transport\Dispatcher;
use Thesis\MessageBus\Transport\OutboundEnvelope;

/**
 * @internal
 */
final class RuntimeHandlerContext extends HandlerContext
{
    /**
     * @var list<OutboundEnvelope>
     */
    public private(set) array $outboundEnvelopes = [];

    /**
     * @param non-empty-string $endpoint
     */
    public function __construct(
        public string $endpoint,
        public Headers $headers,
        private readonly OutboundEnvelopeFactory $envelopeFactory,
        private readonly Dispatcher $dispatcher,
    ) {}

    public function dispatch(Publish|Send|Reply ...$intents): void
    {
        if ($this->dispatchDisabled) {
            throw new \LogicException('Dispatch is disabled after handler execution has finished.');
        }

        foreach ($intents as $intent) {
            $this->outboundEnvelopes[] = $this->envelopeFactory->build(
                intent: $intent,
                originEndpoint: $this->endpoint,
                causeHeaders: $this->headers,
            );
        }
    }

    private bool $dispatchDisabled = false;

    public function disableDispatch(): void
    {
        $this->dispatchDisabled = true;
    }

    public function dispatchImmediately(Publish|Send|Reply ...$intents): void
    {
        if ($intents === []) {
            return;
        }

        $this->dispatcher->dispatch(array_map(
            fn(object $intent) => $this->envelopeFactory->build(
                intent: $intent,
                originEndpoint: $this->endpoint,
                causeHeaders: $this->headers,
            ),
            $intents,
        ));
    }
}
