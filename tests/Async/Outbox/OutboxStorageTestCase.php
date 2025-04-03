<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Async\Outbox;

use PHPUnit\Framework\TestCase;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\TestCommand;

abstract class OutboxStorageTestCase extends TestCase
{
    abstract protected function createStorage(): OutboxStorage;

    final public function testItReturnsNullIfNoOutbox(): void
    {
        $storage = $this->createStorage();

        $outbox = $storage->get('q', 'm');

        self::assertNull($outbox);
    }

    final public function testItStoresInsertedOutbox(): void
    {
        $outbox = new Outbox('m', 'q', [new Envelope(new TestCommand('x'), 'x')]);
        $storage = $this->createStorage();
        $storage->insert($outbox);

        $storedOutbox = $storage->get('m', 'q');

        self::assertEquals($outbox, $storedOutbox);
    }

    final public function testItFailsToInsertIfOutboxAlreadyExists(): void
    {
        $storage = $this->createStorage();
        $storage->insert(new Outbox('m', 'q'));

        $this->expectExceptionObject(new OutboxAlreadyExists());

        $storage->insert(new Outbox('m', 'q'));
    }

    final public function testItStoresUpdatedOutbox(): void
    {
        $storage = $this->createStorage();
        $storage->insert(new Outbox('m', 'q', [new Envelope(new TestCommand('x'), 'x')]));
        $storage->update($newOutbox = new Outbox('m', 'q'));

        $storedOutbox = $storage->get('m', 'q');

        self::assertEquals($newOutbox, $storedOutbox);
    }
}
