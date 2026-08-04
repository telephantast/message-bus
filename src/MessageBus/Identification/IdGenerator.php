<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Identification;

/**
 * @api
 */
interface IdGenerator
{
    /**
     * @return non-empty-string
     */
    public function generateId(): string;
}
