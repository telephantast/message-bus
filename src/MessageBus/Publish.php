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
final class Publish extends Intent
{
    /**
     * @param T $event
     * @param non-empty-string|RoutedCorrelationId|null $correlationId
     */
    public function __construct(
        object $event,
        Headers $headers = new Headers(),
        ?TimeSpan $ttl = null,
        null|string|RoutedCorrelationId $correlationId = null,
        ?TransportOptions $transportOptions = null,
    ) {
        if ($correlationId !== null) {
            $headers = $headers->with(CORRELATION_ID, $correlationId);
        }

        parent::__construct(
            message: $event,
            headers: $headers,
            ttl: $ttl,
            transportOptions: $transportOptions,
        );
    }

    /**
     * @param non-empty-string|RoutedCorrelationId $correlationId
     */
    public function withCorrelationId(string|RoutedCorrelationId $correlationId): static
    {
        return $this->withHeaders($this->headers->with(CORRELATION_ID, $correlationId));
    }
}
