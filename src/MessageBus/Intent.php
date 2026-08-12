<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Headers;
use Thesis\MessageBus\Transport\TransportOptions;
use Thesis\Time\TimeSpan;
use const Thesis\MessageBus\Protocol\CREATED_AT;
use const Thesis\MessageBus\Protocol\EXPIRES_AT;

/**
 * @api
 *
 * @template-covariant T of object = object
 * @phpstan-sealed Send|Publish|Reply
 */
abstract class Intent
{
    public protected(set) Headers $headers;

    /**
     * @param T $message
     */
    public function __construct(
        public readonly object $message,
        Headers $headers,
        ?TimeSpan $ttl,
        public private(set) ?TransportOptions $transportOptions,
    ) {
        $this->headers = $headers->withDefault(CREATED_AT, static fn() => new \DateTimeImmutable());

        if ($ttl !== null) {
            $this->headers = $this->headers->with(
                header: EXPIRES_AT,
                value: self::expiresAt($this->headers->get(CREATED_AT), $ttl),
            );
        }
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

    final public function withTtl(TimeSpan $ttl): static
    {
        $intent = clone $this;
        $intent->headers = $intent->headers->withDefault(
            header: EXPIRES_AT,
            default: self::expiresAt($intent->headers->get(CREATED_AT), $ttl),
        );

        return $intent;
    }

    private static function expiresAt(\DateTimeImmutable $createdAt, TimeSpan $ttl): \DateTimeImmutable
    {
        return $createdAt->modify(\sprintf('%d milliseconds', $ttl->toMilliseconds()));
    }
}
