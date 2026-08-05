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
 * @extends Intent<T>
 */
final class Send extends Intent
{
    /**
     * @param T $command
     * @param ?non-empty-string $destinationEndpoint
     */
    public function __construct(
        object $command,
        public private(set) ?string $destinationEndpoint = null,
        Headers $headers = new Headers(),
        public private(set) TimeSpan $delay = new TimeSpan(0),
        ?TransportOptions $transportOptions = null,
    ) {
        parent::__construct(
            message: $command,
            headers: $headers,
            transportOptions: $transportOptions,
        );
    }

    /**
     * @param ?non-empty-string $endpoint
     */
    public function withDestinationEndpoint(?string $endpoint): static
    {
        $intent = clone $this;
        $intent->destinationEndpoint = $endpoint;

        return $intent;
    }

    public function withDelay(TimeSpan $delay): static
    {
        $intent = clone $this;
        $intent->delay = $delay;

        return $intent;
    }
}
