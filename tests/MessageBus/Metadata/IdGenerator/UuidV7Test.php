<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Metadata\IdGenerator;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(UuidV7::class)]
final readonly class UuidV7Test
{
    public function format(): void
    {
        $id = new UuidV7()->generateId();

        Assert::true(
            preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id) === 1,
        );
    }

    public function monotonic(): void
    {
        $generator = new UuidV7();
        $ids = [];

        for ($i = 0; $i < 10_000; ++$i) {
            $ids[] = $generator->generateId();
        }

        $sorted = $ids;
        sort($sorted);

        Assert::same($ids, $sorted);
    }

    public function unique(): void
    {
        $generator = new UuidV7();
        $ids = [];

        for ($i = 0; $i < 10_000; ++$i) {
            $ids[] = $generator->generateId();
        }

        Assert::same(\count($ids), \count(array_unique($ids)));
    }
}
