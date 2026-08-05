<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Headers;
use Thesis\MessageBus\Transport\TransportOptions;

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
     */
    public function __construct(
        object $event,
        Headers $headers = new Headers(),
        ?TransportOptions $transportOptions = null,
    ) {
        parent::__construct(
            message: $event,
            headers: $headers,
            transportOptions: $transportOptions,
        );
    }
}
