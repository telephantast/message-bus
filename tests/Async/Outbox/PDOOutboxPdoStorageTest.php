<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\Outbox;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(PdoOutboxStorage::class)]
final class PDOOutboxPdoStorageTest extends OutboxStorageTestCase
{
    protected function createStorage(): OutboxStorage
    {
        $storage = new PdoOutboxStorage(new \PDO('sqlite::memory:'));
        $storage->setup();

        return $storage;
    }
}
