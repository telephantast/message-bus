<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Headers;
use Thesis\MessageBus\Transport\TransportOptions;

/**
 * @api
 *
 * @template-covariant T of object = object
 * @phpstan-sealed Send|Publish|Reply
 */
abstract class Intent
{
    public private(set) Headers $headers;

    /**
     * @param T $message
     */
    public function __construct(
        public readonly object $message,
        Headers $headers,
        public private(set) ?TransportOptions $transportOptions,
    ) {
        $this->headers = $headers->withDefault(CREATED_AT, static fn() => new \DateTimeImmutable());
    }

    final public function withHeaders(Headers $headers): static
    {
        $intent = clone $this;
        $intent->headers = $headers;

        return $intent;
    }

    final public function withTransportOptions(?TransportOptions $options): static
    {
        $intent = clone $this;
        $intent->transportOptions = $options;

        return $intent;
    }
}
