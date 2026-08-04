<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Headers;
use Thesis\MessageBus\Transport\TransportOptions;

/**
 * @api
 *
 * @template-covariant T of object = object
 */
final readonly class Publish
{
    public Headers $headers;

    /**
     * @param T $event
     */
    public function __construct(
        public object $event,
        Headers $headers = new Headers(),
        public ?TransportOptions $transportOptions = null,
    ) {
        $this->headers = $headers->withDefault(CREATED_AT, static fn() => new \DateTimeImmutable());
    }
}
