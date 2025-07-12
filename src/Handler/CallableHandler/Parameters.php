<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler\CallableHandler;

use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\Envelope;
use function Typhoon\Formatter\formatReflectedParameter;

final class Parameters
{
    /**
     * @param list<\ReflectionParameter> $parameters
     * @param list<class-string<Parameter>> $classes
     */
    public static function from(array $parameters, array $classes = [
        Parameter\Context::class,
        Parameter\ContextItem::class,
        Parameter\Endpoint::class,
        Parameter\Message::class,
        Parameter\Stamps::class,
    ]): self
    {
        $resolvedParameters = [];

        foreach ($parameters as $reflectionParameter) {
            if ($reflectionParameter->isVariadic()) {
                throw new \LogicException('Variadic');
            }

            if ($reflectionParameter->isPassedByReference()) {
                throw new \LogicException('By ref');
            }

            $matches = [];

            foreach ($classes as $class) {
                $match = $class::tryFrom($reflectionParameter);

                if ($match !== null) {
                    $matches[] = $match;
                }
            }

            if ($matches === []) {
                if ($reflectionParameter->isOptional()) {
                    continue;
                }

                throw new \LogicException(\sprintf('Failed to resolve required parameter `%s`', formatReflectedParameter($reflectionParameter)));
            }

            if (\count($matches) !== 1) {
                throw new \LogicException('Ambiguity');
            }

            $resolvedParameters[$reflectionParameter->name] = $matches[0];
        }

        if (\count(array_filter($resolvedParameters, static fn(Parameter $p): bool => $p instanceof Parameter\Message)) > 1) {
            throw new \LogicException('Cannot have more than 1 message param');
        }

        return new self($resolvedParameters);
    }

    /**
     * @param array<string, Parameter> $parameters
     */
    private function __construct(
        private readonly array $parameters,
    ) {}

    /**
     * @var list<class-string<Message>>
     */
    public array $messageClasses {
        get {
            foreach ($this->parameters as $parameter) {
                if ($parameter instanceof Parameter\Message) {
                    return $parameter->classes;
                }
            }

            return [];
        }
    }

    /**
     * @param non-empty-string $endpoint
     * @param Envelope<*> $envelope
     * @return array<string, mixed>
     */
    public function resolveArguments(string $endpoint, Envelope $envelope, Context $context): array
    {
        return array_map(
            static fn($parameter): mixed => $parameter->resolveArgument($endpoint, $envelope, $context),
            $this->parameters,
        );
    }
}
