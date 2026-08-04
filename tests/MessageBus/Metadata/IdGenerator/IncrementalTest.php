<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Identification;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(IncrementGenerator::class)]
final readonly class IncrementalTest
{
    public function startsAtOne(): void
    {
        Assert::same('1', new IncrementGenerator()->generateId());
    }

    public function customStartValue(): void
    {
        Assert::same('42', new IncrementGenerator(42)->generateId());
    }

    public function sequential(): void
    {
        $generator = new IncrementGenerator();

        Assert::same(['1', '2', '3'], [
            $generator->generateId(),
            $generator->generateId(),
            $generator->generateId(),
        ]);
    }
}
