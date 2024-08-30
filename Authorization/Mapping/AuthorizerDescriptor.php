<?php

declare(strict_types=1);

namespace Telephantast\MessageBus\Authorization\Mapping;

use Telephantast\Message\Message;
use Telephantast\MessageBus\Handler\Mapping\MessageClasses;
use Telephantast\MessageBus\MessageContext;
use Telephantast\MessageBus\Reflection\AttributeReader;
use Telephantast\MessageBus\Reflection\ReflectionStringifier;

/**
 * @api
 * @template-covariant TReflection of \ReflectionFunctionAbstract
 */
final class AuthorizerDescriptor
{
    /**
     * @param TReflection $function
     * @param non-empty-list<class-string<Message>> $messageClasses
     */
    private function __construct(
        public readonly \ReflectionFunctionAbstract $function,
        public readonly array $messageClasses,
    ) {}

    /**
     * @param class-string $passportClass
     * @return list<self<\ReflectionMethod>>
     */
    public static function fromClass(\ReflectionClass $class, string $passportClass): array
    {
        $descriptors = [];

        foreach ($class->getMethods() as $method) {
            if (AttributeReader::firstAttribute($method, Authorizer::class) !== null) {
                $descriptors[] = self::fromFunction($method, $passportClass);
            }
        }

        return $descriptors;
    }

    /**
     * @template TTReflection of \ReflectionFunctionAbstract
     * @param TTReflection $function
     * @param class-string $passportClass
     * @return self<TTReflection>
     */
    public static function fromFunction(\ReflectionFunctionAbstract $function, string $passportClass): self
    {
        if ($function instanceof \ReflectionMethod && !$function->isPublic()) {
            throw new \LogicException(\sprintf('%s must be public', ReflectionStringifier::function($function)));
        }

        if ($function->getNumberOfRequiredParameters() > 2) {
            throw new \LogicException(\sprintf('%s must have at most 2 required parameters', ReflectionStringifier::function($function)));
        }

        $parameters = $function->getParameters();

        if (!isset($parameters[0])) {
            throw new \LogicException(\sprintf('%s must have a message parameter', ReflectionStringifier::function($function)));
        }

        if (isset($parameters[1])) {
            self::checkPassportParameter($parameters[1], $passportClass);
        }

        return new self(
            function: $function,
            messageClasses: MessageClasses::fromParameter($parameters[0]),
        );
    }

    /**
     * @param class-string $class
     */
    private static function checkPassportParameter(\ReflectionParameter $parameter, string $class): void
    {
        if ($parameter->isVariadic()) {
            throw new \LogicException(\sprintf('%s must not be variadic', ReflectionStringifier::parameter($parameter)));
        }

        if ($parameter->isPassedByReference()) {
            throw new \LogicException(\sprintf('%s must not be passed by reference', ReflectionStringifier::parameter($parameter)));
        }

        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType || $type->getName() !== $class || !$type->allowsNull()) {
            throw new \LogicException(\sprintf(
                '%s must have type ?%s, got %s',
                ReflectionStringifier::parameter($parameter),
                MessageContext::class,
                ReflectionStringifier::type($type),
            ));
        }
    }

    /**
     * @return non-empty-string
     */
    public function functionName(): string
    {
        return ReflectionStringifier::function($this->function);
    }
}
