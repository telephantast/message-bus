<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\PostponeEventDispatch;

use Telephantast\MessageBus\ContextAttribute;
use Telephantast\MessageBus\Handler\Pipeline;

/**
 * @internal
 * @psalm-internal Telephantast\MessageBus\PostponeEventDispatch
 */
final class PostponedEventPipelines implements ContextAttribute
{
    /**
     * @var \SplQueue<Pipeline>
     */
    private \SplQueue $pipelines;

    public function __construct()
    {
        /** @var \SplQueue<Pipeline> */
        $pipelines = new \SplQueue();
        $this->pipelines = $pipelines;
    }

    public function add(Pipeline $pipeline): void
    {
        $this->pipelines->enqueue($pipeline);
    }

    public function continue(): void
    {
        while (!$this->pipelines->isEmpty()) {
            $this->pipelines->dequeue()->continue();
        }
    }
}
