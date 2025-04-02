<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

/**
 * @implements Handler<null, TestMessage>
 */
final class TestMessageHandler implements Handler
{
    public function id(): string
    {
        return 'test';
    }

    public function handle(MessageContext $messageContext): mixed
    {
        return null;
    }
}
