<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handling\Mapping;

use Thesis\Message\Message;
use Thesis\MessageBus\Envelope;
use Thesis\MessageBus\Handling\HandleContext;
use function Typhoon\Formatter\formatReflectedFunction;
use function Typhoon\Formatter\formatReflectedParameter;
use function Typhoon\Formatter\formatReflectedType;

/**
 * @api
 * @template-covariant TReflection of \ReflectionFunctionAbstract
 */
final class CallableHandlerDescriptor
{
    /**
     * @param \ReflectionClass<object> $class
     * @return list<self<\ReflectionMethod>>
     */
    public static function fromClass(\ReflectionClass $class): array
    {
        $descriptors = [];

        foreach ($class->getMethods() as $method) {
            if ($method->getAttributes(Handler::class) !== []) {
                $descriptors[] = self::fromFunction($method);
            }
        }

        return $descriptors;
    }

    /**
     * @template TTReflection of \ReflectionFunctionAbstract
     * @param TTReflection $function
     * @return self<TTReflection>
     */
    public static function fromFunction(\ReflectionFunctionAbstract $function): self
    {
        if ($function instanceof \ReflectionMethod && !$function->isPublic()) {
            throw new \LogicException(\sprintf('%s must be public', formatReflectedFunction($function)));
        }

        if ($function->getNumberOfRequiredParameters() > 3) {
            throw new \LogicException(\sprintf('%s must have at most 3 required parameters', formatReflectedFunction($function)));
        }

        $parameters = $function->getParameters();

        if (!isset($parameters[0])) {
            throw new \LogicException(\sprintf('%s must have a message parameter', formatReflectedFunction($function)));
        }

        if (isset($parameters[1])) {
            self::checkHandleContextParameter($parameters[1]);
        }

        if (isset($parameters[2])) {
            self::checkEnvelopeParameter($parameters[2]);
        }

        $attributes = $function->getAttributes(Handler::class);

        if (\count($attributes) > 1) {
            throw new \LogicException(\sprintf('%s must not have at most one #[Handler] attribute', formatReflectedFunction($function)));
        }

        return new self(
            id: ($attributes[0] ?? null)?->newInstance()->id,
            reflection: $function,
            messages: MessageClassParser::fromParameter($parameters[0]),
        );
    }

    private static function checkHandleContextParameter(\ReflectionParameter $parameter): void
    {
        if ($parameter->isVariadic()) {
            throw new \LogicException(\sprintf('%s must not be variadic', formatReflectedParameter($parameter)));
        }

        if ($parameter->isPassedByReference()) {
            throw new \LogicException(\sprintf('%s must not be passed by reference', formatReflectedParameter($parameter)));
        }

        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType || $type->getName() !== HandleContext::class) {
            throw new \LogicException(\sprintf(
                '%s must have type %s, got %s',
                formatReflectedParameter($parameter),
                HandleContext::class,
                formatReflectedType($type),
            ));
        }
    }

    private static function checkEnvelopeParameter(\ReflectionParameter $parameter): void
    {
        if ($parameter->isVariadic()) {
            throw new \LogicException(\sprintf('%s must not be variadic', formatReflectedParameter($parameter)));
        }

        if ($parameter->isPassedByReference()) {
            throw new \LogicException(\sprintf('%s must not be passed by reference', formatReflectedParameter($parameter)));
        }

        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType || $type->getName() !== Envelope::class) {
            throw new \LogicException(\sprintf(
                '%s must have type %s, got %s',
                formatReflectedParameter($parameter),
                Envelope::class,
                formatReflectedType($type),
            ));
        }
    }

    /**
     * @param ?non-empty-string $id
     * @param TReflection $reflection
     * @param non-empty-list<class-string<Message>> $messages
     */
    private function __construct(
        public readonly ?string $id,
        public readonly \ReflectionFunctionAbstract $reflection,
        public readonly array $messages,
    ) {}

    public bool $isStatic { get => $this->reflection->isStatic(); }
}
