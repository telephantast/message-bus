<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

interface Sender
{
    /**
     * @no-named-arguments
     */
    public function send(object ...$commands): void;
}
