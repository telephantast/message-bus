<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Pgmq\Internal;

use Amp\Cancellation;
use Amp\NullCancellation;
use Thesis\MessageBus\ConsumerRuntime\Consumer as BusConsumer;
use Thesis\Pgmq\ConsumeContext;

/**
 * @internal
 */
final readonly class Consumer implements BusConsumer
{
    public function __construct(
        private ConsumeContext $context,
    ) {}

    public function stop(): void
    {
        $this->context->stop();
    }

    public function awaitCompletion(Cancellation $cancellation = new NullCancellation()): void
    {
        $this->context->awaitCompletion($cancellation);
    }
}
