<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\MessageMatcher;

/**
 * @internal
 * @template TKey of int|string
 */
final class Router
{
    /**
     * @var array<class-string, TKey>
     */
    private array $destination = [];

    /**
     * @param array<TKey, MessageMatcher> $matchers
     */
    public function __construct(
        private readonly array $matchers = [],
    ) {}

    /**
     * @param class-string $messageClass
     * @return TKey
     */
    public function route(string $messageClass): int|string
    {
        return $this->destination[$messageClass] ??= $this->resolve($messageClass);
    }

    /**
     * @param class-string $messageClass
     * @return TKey
     */
    private function resolve(string $messageClass): int|string
    {
        $keys = [];

        foreach ($this->matchers as $key => $matcher) {
            if ($matcher->matches($messageClass)) {
                $keys[] = $key;
            }
        }

        if (\count($keys) !== 1) {
            throw new \LogicException();
        }

        return $keys[0];
    }
}
