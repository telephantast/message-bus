<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\EntityHandler;

/**
 * @api
 */
interface EntitySaver
{
    public function save(object $entity): void;
}
