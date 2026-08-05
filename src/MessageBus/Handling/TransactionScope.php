<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling;

/**
 * @api
 *
 * @template-covariant Tx of object
 */
interface TransactionScope
{
    /**
     * The persistence-specific transaction handle available to the handler.
     * Implementation may begin the underlying transaction when this handle is first used.
     *
     * @var Tx
     */
    public object $handle { get; }
}
