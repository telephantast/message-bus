<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @api
 *
 * @template-covariant Tx of object
 */
interface HandlerContext
{
    /**
     * @var non-empty-string
     */
    public string $endpoint { get; }

    /**
     * @var Tx
     */
    public object $transaction { get; }

    /**
     * @no-named-arguments
     */
    public function send(object ...$commands): void;

    /**
     * @no-named-arguments
     */
    public function publish(object ...$events): void;

    public function reply(object $reply): void;
}
