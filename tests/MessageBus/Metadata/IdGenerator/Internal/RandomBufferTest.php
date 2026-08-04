<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Identification\Internal;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\MessageBus\Internal\RandomBuffer;

#[Test]
#[Covers(RandomBuffer::class)]
final readonly class RandomBufferTest
{
    public function returnsUniqueValues(): void
    {
        $buffer = new RandomBuffer();
        $values = [];

        for ($i = 0; $i < 128; ++$i) {
            $values[] = $buffer->nextInt64();
        }

        Assert::same(\count($values), \count(array_unique($values)));
    }

    public function worksAcrossBufferBoundary(): void
    {
        $buffer = new RandomBuffer();

        // 512 / 8 = 64 values per buffer fill; generate enough to cross multiple boundaries
        for ($i = 0; $i < 192; ++$i) {
            $buffer->nextInt64();
        }

        Assert::int($buffer->nextInt64());
    }
}
