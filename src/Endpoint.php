<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessor;
use Thesis\MessageBus\Dispatching\OutgoingEnvelopeProcessors;
use Thesis\MessageBus\Handling\ArrayHandlerRegistry;
use Thesis\MessageBus\Handling\HandlerRegistry;
use Thesis\MessageBus\Internal\Session;
use Thesis\MessageBus\Persistence\Storage;
use Thesis\MessageBus\Persistence\StorageSetup;
use Thesis\MessageBus\Tracing\AddCauseIdToOutgoingEnvelope;
use Thesis\MessageBus\Tracing\AddCorrelationIdToOutgoingEnvelope;
use Thesis\MessageBus\Tracing\AddMessageIdToOutgoingEnvelope;
use Thesis\MessageBus\Tracing\AddSourceEndpointToOutgoingEnvelope;
use Thesis\MessageBus\Tracing\AddTimestampToOutgoingEnvelope;
use Thesis\MessageBus\Tracing\EnsureOutgoingEnvelopeHasMessageId;
use Thesis\MessageBus\Transport\Transport;

/**
 * @api
 * @template-covariant TTransaction of object = object
 */
final class Endpoint
{
    /**
     * @return list<OutgoingEnvelopeProcessor>
     */
    public static function defaultOutgoingEnvelopeProcessors(): array
    {
        return [
            new AddMessageIdToOutgoingEnvelope(),
            new AddCauseIdToOutgoingEnvelope(),
            new AddCorrelationIdToOutgoingEnvelope(),
            new AddTimestampToOutgoingEnvelope(),
            new AddSourceEndpointToOutgoingEnvelope(),
        ];
    }

    private readonly OutgoingEnvelopeProcessor $outgoingEnvelopeProcessor;

    /**
     * @var ?\Closure(): void
     */
    private ?\Closure $cancel = null;

    /**
     * @param non-empty-string $name
     * @param Storage<TTransaction> $storage
     * @param HandlerRegistry<TTransaction> $asyncHandlerRegistry
     * @param HandlerRegistry<TTransaction> $syncHandlerRegistry
     * @param ?list<OutgoingEnvelopeProcessor> $outgoingEnvelopeProcessors
     */
    public function __construct(
        private readonly string $name,
        private readonly Storage $storage,
        private readonly Transport $transport,
        private readonly HandlerRegistry $asyncHandlerRegistry = new ArrayHandlerRegistry(),
        private readonly HandlerRegistry $syncHandlerRegistry = new ArrayHandlerRegistry(),
        ?array $outgoingEnvelopeProcessors = null,
    ) {
        if ($outgoingEnvelopeProcessors === null) {
            $outgoingEnvelopeProcessors = self::defaultOutgoingEnvelopeProcessors();
        } else {
            $outgoingEnvelopeProcessors[] = new EnsureOutgoingEnvelopeHasMessageId();
        }

        $this->outgoingEnvelopeProcessor = new OutgoingEnvelopeProcessors($outgoingEnvelopeProcessors);
    }

    private bool $setup = false;

    public function setup(): void
    {
        if ($this->setup) {
            return;
        }

        $this->transport->setup(
            endpoint: $this->name,
            localMessages: $this->asyncHandlerRegistry->messages,
        );

        if ($this->storage instanceof StorageSetup) {
            $this->storage->setup();
        }

        $this->setup = true;
    }

    public function dispatch(Message|Envelope $message): void
    {
        $this->setup();

        Session::dispatchFromEndpoint(
            endpoint: $this->name,
            storage: $this->storage,
            transport: $this->transport,
            syncHandlerRegistry: $this->syncHandlerRegistry,
            outgoingEnvelopeProcessor: $this->outgoingEnvelopeProcessor,
            envelope: Envelope::wrap($message),
        );
    }

    public function run(): void
    {
        $this->setup();

        $this->cancel ??= $this->transport->consume(
            endpoint: $this->name,
            handler: fn(Envelope $envelope): null => Session::consume(
                endpoint: $this->name,
                storage: $this->storage,
                transport: $this->transport,
                asyncHandlerRegistry: $this->asyncHandlerRegistry,
                syncHandlerRegistry: $this->syncHandlerRegistry,
                outgoingEnvelopeProcessor: $this->outgoingEnvelopeProcessor,
                envelope: $envelope,
            ),
        );
    }

    public function stop(): void
    {
        if ($this->cancel !== null) {
            ($this->cancel)();
            $this->cancel = null;
        }
    }
}
