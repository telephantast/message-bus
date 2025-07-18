<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

interface Publisher
{
    /**
     * @no-named-arguments
     */
    public function publish(object ...$events): void;
}
