<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

use Thesis\MessageBus\Persistence\Transaction as TTransaction;

/**
 * @api
 * @template-covariant TTransaction of Transaction = Transaction
 * @extends Storage<TTransaction>
 */
interface StorageSetup extends Storage
{
    public function setup(): void;
}
