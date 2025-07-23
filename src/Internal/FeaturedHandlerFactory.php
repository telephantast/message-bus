<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Internal;

use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handler\Middleware;
use Thesis\MessageBus\Handler\Pipeline;
use Thesis\MessageBus\Handler\Result;
use Thesis\MessageBus\Stamps;

final readonly class FeaturedHandlerFactory
{
    /**
     * @template TMessage of object
     * @template TTransaction of object = never
     * @param callable(TMessage, Context<object, TTransaction>, Stamps): mixed $handler
     * @param list<Middleware> $middleware
     * @return array{non-empty-list<class-string<TMessage>>, \Closure(Envelope<TMessage>, Context<object, TTransaction>): mixed}
     */
    public static function create(callable $handler, array $middleware): array
    {
        $reflection = new \ReflectionFunction($handler(...));

        $messageParameter = $reflection->getParameters()[0] ?? throw new \LogicException();
        /** @var list<class-string<TMessage>> */
        $messageClasses = self::parseType($messageParameter->getType());

        if ($messageClasses === []) {
            throw new \LogicException('Cannot infer message classes');
        }

        return [
            $messageClasses,
            static function (Envelope $envelope, Context $context) use ($handler, $middleware): mixed {
                if ($middleware === []) {
                    /** @phpstan-ignore argument.type */
                    $result = $handler($envelope->message, $context, $envelope->stamps);
                } else {
                    /** @phpstan-ignore argument.type */
                    $result = new Pipeline($handler, $middleware, $envelope, $context)->continue();
                }

                if ($result instanceof Result) {
                    return $context->processResult($result);
                }

                return $result;
            },
        ];
    }

    /**
     * @return list<class-string>
     */
    private static function parseType(?\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionNamedType) {
            $name = $type->getName();

            if (class_exists($name)) {
                return [$name];
            }

            return [];
        }

        if ($type instanceof \ReflectionUnionType) {
            return array_merge(...array_map(self::parseType(...), $type->getTypes()));
        }

        return [];
    }

    private function __construct() {}
}
