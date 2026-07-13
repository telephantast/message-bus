<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template-contravariant Tx of object
 */
final readonly class EndpointConfig
{
    /**
     * @param non-empty-string $name
     * @param Handlers<Tx> $handlers
     * @param Listeners<Tx> $listeners
     */
    public function __construct(
        public string $name,
        public Handlers $handlers = new Handlers(),
        public Listeners $listeners = new Listeners(),
    ) {}
}
