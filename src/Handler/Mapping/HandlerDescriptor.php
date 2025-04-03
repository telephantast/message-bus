<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler\Mapping;

use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use function Thesis\MessageBus\Internal\firstFunctionAttribute;
use function Typhoon\Describe\describeReflectedDeclaration;
use function Typhoon\Describe\describeReflectedType;

/**
 * @api
 * @template-covariant TReflection of \ReflectionFunctionAbstract
 */
final readonly class HandlerDescriptor
{
    /**
     * @param \ReflectionClass<object> $class
     * @return list<self<\ReflectionMethod>>
     */
    public static function fromClass(\ReflectionClass $class): array
    {
        $descriptors = [];

        foreach ($class->getMethods() as $method) {
            if (firstFunctionAttribute($method, Handler::class) !== null) {
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
            throw new \LogicException(\sprintf('%s must be public', describeReflectedDeclaration($function)));
        }

        if ($function->getNumberOfRequiredParameters() > 2) {
            throw new \LogicException(\sprintf('%s must have at most 2 required parameters', describeReflectedDeclaration($function)));
        }

        $parameters = $function->getParameters();

        if (!isset($parameters[0])) {
            throw new \LogicException(\sprintf('%s must have a message parameter', describeReflectedDeclaration($function)));
        }

        if (isset($parameters[1])) {
            self::checkMessageContextParameter($parameters[1]);
        }

        return new self(
            id: firstFunctionAttribute($function, Handler::class)?->newInstance()->id,
            reflection: $function,
            messageClasses: MessageClasses::fromParameter($parameters[0]),
        );
    }

    private static function checkMessageContextParameter(\ReflectionParameter $parameter): void
    {
        if ($parameter->isVariadic()) {
            throw new \LogicException(\sprintf('%s must not be variadic', describeReflectedDeclaration($parameter)));
        }

        if ($parameter->isPassedByReference()) {
            throw new \LogicException(\sprintf('%s must not be passed by reference', describeReflectedDeclaration($parameter)));
        }

        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType || $type->getName() !== Context::class) {
            throw new \LogicException(\sprintf(
                '%s must have type %s, got %s',
                describeReflectedDeclaration($parameter),
                Context::class,
                describeReflectedType($type),
            ));
        }
    }

    /**
     * @param ?non-empty-string $id
     * @param TReflection $reflection
     * @param non-empty-list<class-string<Message>> $messageClasses
     */
    private function __construct(
        public ?string $id,
        public \ReflectionFunctionAbstract $reflection,
        public array $messageClasses,
    ) {}
}
