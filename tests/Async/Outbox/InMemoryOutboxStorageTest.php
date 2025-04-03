<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\Outbox;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InMemoryOutboxStorage::class)]
#[CoversClass(Outbox::class)]
#[CoversClass(OutboxAlreadyExists::class)]
final class InMemoryOutboxStorageTest extends OutboxStorageTestCase
{
    protected function createStorage(): OutboxStorage
    {
        return new InMemoryOutboxStorage();
    }
}
