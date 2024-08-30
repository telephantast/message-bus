<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\PostponedEventHandling;

use Telephantast\MessageBus\Handler\Pipeline;
use Telephantast\MessageBus\InheritableContextAttribute;

/**
 * @internal
 * @psalm-internal Telephantast\MessageBus\PostponedEventHandling
 */
final class PostponedEventPipelines implements InheritableContextAttribute
{
    /**
     * @var \SplQueue<Pipeline>
     */
    private readonly \SplQueue $pipelines;

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
