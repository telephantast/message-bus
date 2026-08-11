<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Testing;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(SequentialIdGenerator::class)]
final readonly class SequentialIdGeneratorTest
{
    public function startsAtOne(): void
    {
        Assert::same('1', new SequentialIdGenerator()->generateId());
    }

    public function customStartValue(): void
    {
        Assert::same('42', new SequentialIdGenerator(42)->generateId());
    }

    public function sequential(): void
    {
        $generator = new SequentialIdGenerator();

        Assert::same(['1', '2', '3'], [
            $generator->generateId(),
            $generator->generateId(),
            $generator->generateId(),
        ]);
    }
}
