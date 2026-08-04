<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Headers;
use Thesis\MessageBus\Transport\TransportOptions;
use Thesis\Time\TimeSpan;

/**
 * @api
 *
 * @template-covariant T of object = object
 */
final readonly class Send
{
    public Headers $headers;

    /**
     * @param T $command
     * @param ?non-empty-string $destinationEndpoint
     */
    public function __construct(
        public object $command,
        public ?string $destinationEndpoint = null,
        Headers $headers = new Headers(),
        public TimeSpan $delay = new TimeSpan(0),
        public ?TransportOptions $transportOptions = null,
    ) {
        $this->headers = $headers->withDefault(CREATED_AT, static fn() => new \DateTimeImmutable());
    }
}
