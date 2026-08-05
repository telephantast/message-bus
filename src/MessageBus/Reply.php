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
final class Reply extends Intent
{
    /**
     * @param T $reply
     */
    public function __construct(
        object $reply,
        public readonly ?ReplyTo $to = null,
        Headers $headers = new Headers(),
        ?TransportOptions $transportOptions = null,
    ) {
        parent::__construct(
            message: $reply,
            headers: $headers,
            transportOptions: $transportOptions,
        );
    }
}
