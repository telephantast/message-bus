<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Transport;

use Thesis\Message\Command;
use Thesis\MessageBus\Call;
use Thesis\MessageBus\Envelope;

interface Router
{
    /**
     * @param Envelope<Call<*>|Command> $envelope
     * @return ?non-empty-string
     */
    public function route(Envelope $envelope): ?string;
}
