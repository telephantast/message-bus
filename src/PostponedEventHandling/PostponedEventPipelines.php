<?php

declare(strict_types=1);

namespace Thesis\MessageBus\PostponedEventHandling;

use Thesis\Message\Event;
use Thesis\MessageBus\InheritableContextAttribute;
use Thesis\MessageBus\Pipeline;

/**
 * @internal
 * @psalm-internal Thesis\MessageBus\PostponedEventHandling
 */
final readonly class PostponedEventPipelines implements InheritableContextAttribute
{
    /**
     * @var \SplQueue<Pipeline<null, Event>>
     */
    private \SplQueue $pipelines;

    public function __construct()
    {
        /** @var \SplQueue<Pipeline<null, Event>> */
        $pipelines = new \SplQueue();
        $this->pipelines = $pipelines;
    }

    /**
     * @param Pipeline<null, Event> $pipeline
     */
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
