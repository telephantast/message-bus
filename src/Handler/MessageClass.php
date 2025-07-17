<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Handler;

use Thesis\Message\Call;
use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\Message\Message;

/**
 * @template TMessage of Message
 */
final class MessageClass
{
    /**
     * @template TFromMessage of Message
     * @param class-string<TFromMessage>|TFromMessage|\ReflectionClass<TFromMessage> $class
     * @return self<TFromMessage>
     */
    public static function from(string|object $class): self
    {
        if (!$class instanceof \ReflectionClass) {
            $class = new \ReflectionClass($class);
        }

        if (!$class->isFinal()) {
            throw new \LogicException();
        }

        if (!(
            $class->implementsInterface(Command::class)
            xor $class->implementsInterface(Event::class)
            xor $class->implementsInterface(Call::class)
        )) {
            throw new \LogicException();
        }

        return new self($class);
    }

    /**
     * @var class-string<TMessage>
     */
    public string $name { get => $this->reflection->name; }

    public bool $isCommand { get => $this->reflection->implementsInterface(Command::class); }

    public bool $isEvent { get => $this->reflection->implementsInterface(Event::class); }

    public bool $isCall { get => $this->reflection->implementsInterface(Call::class); }

    /**
     * @param \ReflectionClass<TMessage> $reflection
     */
    private function __construct(
        public readonly \ReflectionClass $reflection,
    ) {}
}
