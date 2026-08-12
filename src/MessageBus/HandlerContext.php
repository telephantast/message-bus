<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Headers;
use Thesis\MessageBus\Protocol\RoutedCorrelationId;
use Thesis\MessageBus\Routing\CannotRouteCommand;
use Thesis\MessageBus\Transport\TransportOptions;
use Thesis\Time\TimeSpan;
use const Thesis\MessageBus\Protocol\CORRELATION_ID;

/**
 * @api
 */
abstract class HandlerContext
{
    /**
     * @var non-empty-string
     */
    abstract public string $endpoint { get; }

    abstract public Headers $headers { get; }

    /**
     * @phpstan-ignore property.uninitialized
     */
    final public ReplyTo $replyTo {
        get => $this->replyTo ??= ReplyTo::fromHeaders($this->headers);
    }

    /**
     * @var ?non-empty-string
     */
    final public ?string $correlationId {
        get {
            $correlationId = $this->headers->find(CORRELATION_ID);

            if ($correlationId instanceof RoutedCorrelationId) {
                return $correlationId->id;
            }

            return $correlationId;
        }
    }

    /**
     * @param ?non-empty-string $endpoint
     * @param non-empty-string|RoutedCorrelationId|null $correlationId
     *
     * @throws InvalidIntent
     * @throws CannotRouteCommand
     */
    final public function send(
        object $command,
        ?string $endpoint = null,
        Headers $headers = new Headers(),
        TimeSpan $delay = new TimeSpan(0),
        ?TimeSpan $ttl = null,
        null|string|RoutedCorrelationId $correlationId = null,
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatch(
            new Send(
                command: $command,
                destination: $endpoint,
                headers: $headers,
                delay: $delay,
                ttl: $ttl,
                correlationId: $correlationId,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @param non-empty-string|RoutedCorrelationId|null $correlationId
     *
     * @throws InvalidIntent
     */
    final public function publish(
        object $event,
        Headers $headers = new Headers(),
        ?TimeSpan $ttl = null,
        null|string|RoutedCorrelationId $correlationId = null,
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatch(
            new Publish(
                event: $event,
                headers: $headers,
                ttl: $ttl,
                correlationId: $correlationId,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @throws InvalidIntent
     */
    final public function reply(
        object $reply,
        ?ReplyTo $to = null,
        Headers $headers = new Headers(),
        ?TimeSpan $ttl = null,
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatch(
            new Reply(
                reply: $reply,
                to: $to,
                headers: $headers,
                ttl: $ttl,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @no-named-arguments
     *
     * @throws InvalidIntent
     * @throws CannotRouteCommand
     */
    abstract public function dispatch(Send|Publish|Reply ...$intents): void;

    /**
     * @param ?non-empty-string $endpoint
     * @param non-empty-string|RoutedCorrelationId|null $correlationId
     *
     * @throws InvalidIntent
     * @throws CannotRouteCommand
     */
    final public function sendImmediately(
        object $command,
        ?string $endpoint = null,
        Headers $headers = new Headers(),
        TimeSpan $delay = new TimeSpan(0),
        ?TimeSpan $ttl = null,
        null|string|RoutedCorrelationId $correlationId = null,
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatchImmediately(
            new Send(
                command: $command,
                destination: $endpoint,
                headers: $headers,
                delay: $delay,
                ttl: $ttl,
                correlationId: $correlationId,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @param non-empty-string|RoutedCorrelationId|null $correlationId
     *
     * @throws InvalidIntent
     */
    final public function publishImmediately(
        object $event,
        Headers $headers = new Headers(),
        ?TimeSpan $ttl = null,
        null|string|RoutedCorrelationId $correlationId = null,
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatchImmediately(
            new Publish(
                event: $event,
                headers: $headers,
                ttl: $ttl,
                correlationId: $correlationId,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @throws InvalidIntent
     */
    final public function replyImmediately(
        object $reply,
        ?ReplyTo $to = null,
        Headers $headers = new Headers(),
        ?TimeSpan $ttl = null,
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatchImmediately(
            new Reply(
                reply: $reply,
                to: $to,
                headers: $headers,
                ttl: $ttl,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @no-named-arguments
     *
     * @throws InvalidIntent
     * @throws CannotRouteCommand
     */
    abstract public function dispatchImmediately(Send|Publish|Reply ...$intents): void;
}
