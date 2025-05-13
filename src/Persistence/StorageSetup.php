<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 * @template-covariant TWrappedTransaction of object = object
 * @template-covariant TTransaction of Transaction<TWrappedTransaction> = Transaction<object>
 * @extends Storage<TWrappedTransaction, TTransaction>
 */
interface StorageSetup extends Storage
{
    public function setup(): void;
}
