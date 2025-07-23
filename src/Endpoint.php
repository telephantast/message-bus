<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

final readonly class Endpoint
{
    /**
     * @param non-empty-string $name
     */
    public static function consumer(string $name): self
    {
        return new self(EndpointType::Consumer, $name);
    }

    /**
     * @param non-empty-string $name
     */
    public static function subscription(string $name): self
    {
        return new self(EndpointType::Subscription, $name);
    }

    /**
     * @param non-empty-string $name
     */
    public static function service(string $name): self
    {
        return new self(EndpointType::Service, $name);
    }

    /**
     * @param non-empty-string $name
     */
    private function __construct(
        public EndpointType $type,
        public string $name,
    ) {}

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return \sprintf('%s.%s', $this->type->value, $this->name);
    }
}
