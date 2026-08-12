<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Headers;
use Thesis\MessageBus\Protocol\RoutedCorrelationId;
use Thesis\MessageBus\Transport\TransportOptions;
use Thesis\Time\TimeSpan;
use const Thesis\MessageBus\Protocol\CORRELATION_ID;

/**
 * @api
 *
 * @template-covariant T of object = object
 * @extends Intent<T>
 */
final class Send extends Intent
{
    /**
     * @param T $command
     * @param ?non-empty-string $destination Endpoint name
     * @param non-empty-string|RoutedCorrelationId|null $correlationId
     */
    public function __construct(
        object $command,
        public private(set) ?string $destination = null,
        Headers $headers = new Headers(),
        public private(set) TimeSpan $delay = new TimeSpan(0),
        ?TimeSpan $ttl = null,
        null|string|RoutedCorrelationId $correlationId = null,
        ?TransportOptions $transportOptions = null,
    ) {
        if ($correlationId !== null) {
            $headers = $headers->with(CORRELATION_ID, $correlationId);
        }

        parent::__construct(
            message: $command,
            headers: $headers,
            ttl: $ttl,
            transportOptions: $transportOptions,
        );
    }

    /**
     * @param ?non-empty-string $endpoint
     */
    public function withDestination(?string $endpoint): static
    {
        $intent = clone $this;
        $intent->destination = $endpoint;

        return $intent;
    }

    public function withDelay(TimeSpan $delay): static
    {
        $intent = clone $this;
        $intent->delay = $delay;

        return $intent;
    }

    /**
     * @param non-empty-string|RoutedCorrelationId $correlationId
     */
    public function withCorrelationId(string|RoutedCorrelationId $correlationId): static
    {
        return $this->withHeaders($this->headers->with(CORRELATION_ID, $correlationId));
    }
}
