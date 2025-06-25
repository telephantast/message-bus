<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Persistence;

/**
 * @api
 * @template-covariant TTransaction of object
 * @extends Storage<TTransaction>
 */
interface StorageSetup extends Storage
{
    public function setup(): void;
}
