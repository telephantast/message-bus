<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Message\Message;

/**
 * @template-covariant TResult = null
 * @template-covariant TMessage of Message<TResult> = Message<null>
 */
final readonly class Envelope
{
    /**
     * @param TMessage $message
     */
    public function __construct(
        public Message $message,
        public Stamps $stamps = new Stamps(),
    ) {}
}
