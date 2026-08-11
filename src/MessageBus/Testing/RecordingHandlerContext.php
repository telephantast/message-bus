<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Testing;

use Thesis\Headers;
use Thesis\MessageBus\HandlerContext;
use Thesis\MessageBus\Publish;
use Thesis\MessageBus\Reply;
use Thesis\MessageBus\Send;

/**
 * @api
 */
final class RecordingHandlerContext extends HandlerContext
{
    /**
     * @param non-empty-string $endpoint
     */
    public function __construct(
        public readonly string $endpoint,
        public readonly Headers $headers,
    ) {}

    /**
     * @var list<Publish|Send|Reply>
     */
    public private(set) array $dispatched = [];

    public function dispatch(Publish|Send|Reply ...$intents): void
    {
        foreach ($intents as $intent) {
            $this->dispatched[] = $intent;
        }
    }

    /**
     * @var list<Publish|Send|Reply>
     */
    public private(set) array $dispatchedImmediately = [];

    public function dispatchImmediately(Publish|Send|Reply ...$intents): void
    {
        foreach ($intents as $intent) {
            $this->dispatchedImmediately[] = $intent;
        }
    }
}
