<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Pgmq\Internal;

use Amp\Cancellation;
use Amp\Future;
use Amp\NullCancellation;
use Amp\Pipeline;
use Thesis\MessageBus\Transport\Consumer;
use Thesis\Pgmq\Internal\PollWatcher;

/**
 * @internal
 */
final readonly class PgmqConsumer implements Consumer
{
    /**
     * @param Pipeline\Queue<null> $polls
     * @param Future<void> $completion
     */
    public function __construct(
        private Pipeline\Queue $polls,
        private PollWatcher $watcher,
        private Future $completion,
    ) {}

    public function stop(): void
    {
        if (!$this->polls->isComplete()) {
            $this->watcher->cancel();
            $this->polls->complete();
        }
    }

    public function awaitCompletion(Cancellation $cancellation = new NullCancellation()): void
    {
        $this->completion->await($cancellation);
    }
}
