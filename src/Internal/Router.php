<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\MessageMatcher;

/**
 * @todo add generic?
 */
final class Router
{
    /**
     * @var array<class-string, non-empty-string>
     */
    private array $commandEndpoints = [];

    /**
     * @param array<non-empty-string, MessageMatcher> $matchers
     */
    public function __construct(
        private readonly array $matchers,
    ) {}

    /**
     * @param class-string $messageClass
     * @return non-empty-string
     */
    public function route(string $messageClass): string
    {
        return $this->commandEndpoints[$messageClass] ??= $this->resolve($messageClass);
    }

    /**
     * @param class-string $messageClass
     * @return non-empty-string
     */
    private function resolve(string $messageClass): string
    {
        $endpoints = [];

        foreach ($this->matchers as $endpoint => $matcher) {
            if ($matcher->matches($messageClass)) {
                $endpoints[] = $endpoint;
            }
        }

        if (\count($endpoints) !== 1) {
            throw new \LogicException();
        }

        return $endpoints[0];
    }
}
