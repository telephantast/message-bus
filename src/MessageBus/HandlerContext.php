<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Headers;
use Thesis\MessageBus\Transport\TransportOptions;
use Thesis\Time\TimeSpan;

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
        get => $this->replyTo ??= ReplyTo::fromRequestHeaders($this->headers);
    }

    /**
     * @param ?non-empty-string $endpoint
     *
     * @throws InvalidOutboundMessage
     */
    final public function send(
        object $command,
        ?string $endpoint = null,
        Headers $headers = new Headers(),
        TimeSpan $delay = new TimeSpan(0),
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatch(
            new Send(
                command: $command,
                destinationEndpoint: $endpoint,
                headers: $headers,
                delay: $delay,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @throws InvalidOutboundMessage
     */
    final public function publish(
        object $event,
        Headers $headers = new Headers(),
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatch(
            new Publish(
                event: $event,
                headers: $headers,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @throws InvalidOutboundMessage
     */
    final public function reply(
        object $reply,
        ?ReplyTo $to = null,
        Headers $headers = new Headers(),
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatch(
            new Reply(
                reply: $reply,
                to: $to,
                headers: $headers,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @no-named-arguments
     *
     * @throws InvalidOutboundMessage
     */
    abstract public function dispatch(Send|Publish|Reply ...$intents): void;

    /**
     * @param ?non-empty-string $endpoint
     *
     * @throws InvalidOutboundMessage
     */
    final public function sendImmediately(
        object $command,
        ?string $endpoint = null,
        Headers $headers = new Headers(),
        TimeSpan $delay = new TimeSpan(0),
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatchImmediately(
            new Send(
                command: $command,
                destinationEndpoint: $endpoint,
                headers: $headers,
                delay: $delay,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @throws InvalidOutboundMessage
     */
    final public function publishImmediately(
        object $event,
        Headers $headers = new Headers(),
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatchImmediately(
            new Publish(
                event: $event,
                headers: $headers,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @throws InvalidOutboundMessage
     */
    final public function replyImmediately(
        object $reply,
        ?ReplyTo $to = null,
        Headers $headers = new Headers(),
        ?TransportOptions $transportOptions = null,
    ): void {
        $this->dispatchImmediately(
            new Reply(
                reply: $reply,
                to: $to,
                headers: $headers,
                transportOptions: $transportOptions,
            ),
        );
    }

    /**
     * @no-named-arguments
     *
     * @throws InvalidOutboundMessage
     */
    abstract public function dispatchImmediately(Send|Publish|Reply ...$intents): void;
}
