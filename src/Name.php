<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

interface Name
{
    /**
     * @return non-empty-string
     */
    public function toString(): string;
}
