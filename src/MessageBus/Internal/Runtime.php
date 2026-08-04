<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Processing\ProcessingId;
use Thesis\MessageBus\Transport\Dispatcher;
use Thesis\MessageBus\Transport\InboundEnvelope;
use Thesis\MessageBus\Transport\OutboundEnvelope;

/**
 * @internal
 *
 * @template-covariant Tx of object
 */
interface Runtime
{
    /**
     * Dispatches the given envelopes as a new operation.
     *
     * Implementations must atomically accept all envelopes.
     * This method is not retry-safe; use {@see dispatchIdempotently()} when
     * the caller can retry after an ambiguous failure.
     *
     * @param non-empty-string $endpoint
     * @param non-empty-list<OutboundEnvelope> $envelopes
     */
    public function dispatch(string $endpoint, array $envelopes): void;

    /**
     * Dispatches the given envelopes as a caller-identified operation.
     *
     * Implementations must atomically accept all envelopes.
     * Repeating the same processing id must not create a second logical
     * dispatch operation.
     *
     * @param non-empty-list<OutboundEnvelope> $envelopes
     */
    public function dispatchIdempotently(ProcessingId $id, array $envelopes): void;

    /**
     * Handles an in-process message that does not have a stable incoming id.
     *
     * Implementations must atomically accept the handler's transactional work
     * and outgoing envelopes. This method is not retry-safe; use
     * {@see handleIdempotently()} when the caller can retry after an ambiguous
     * failure.
     *
     * @param non-empty-string $endpoint
     * @param callable(Tx, Dispatcher): list<OutboundEnvelope> $handler
     */
    public function handle(string $endpoint, callable $handler): void;

    /**
     * Handles an in-process message with a stable incoming id.
     *
     * Implementations must atomically record the processing identity and accept
     * the handler's transactional work and outgoing envelopes. Repeating the
     * same processing id must not execute the handler's business effect twice.
     *
     * @param callable(Tx, Dispatcher): list<OutboundEnvelope> $handler
     */
    public function handleIdempotently(ProcessingId $id, callable $handler): void;

    /**
     * Consumes one inbound transport envelope.
     *
     * Implementations must use the processing id as the consumption identity
     * and atomically accept the handler's transactional work and outgoing
     * envelopes. After this method returns successfully, the incoming message
     * can be acknowledged without losing outgoing envelopes.
     *
     * @param callable(Tx, Dispatcher): list<OutboundEnvelope> $handler
     */
    public function consumeIdempotently(ProcessingId $id, InboundEnvelope $envelope, callable $handler): void;
}
