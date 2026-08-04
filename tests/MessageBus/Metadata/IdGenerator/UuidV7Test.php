<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Identification;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(UuidV7Generator::class)]
final readonly class UuidV7Test
{
    public function format(): void
    {
        $id = new UuidV7Generator()->generateId();

        Assert::true(
            preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id) === 1,
        );
    }

    public function monotonic(): void
    {
        $generator = new UuidV7Generator();
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
        $generator = new UuidV7Generator();
        $ids = [];

        for ($i = 0; $i < 10_000; ++$i) {
            $ids[] = $generator->generateId();
        }

        Assert::same(\count($ids), \count(array_unique($ids)));
    }
}
