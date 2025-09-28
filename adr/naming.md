# API

## Producer/Consumer

Producer sends Commands to Consumer.

```php
final readonly class Register {}

function recommended(): Result
{
    return send(new Register());
}

function lowLevel(Context $context): void
{
    $context->send(new Register());
}

function entrypoint(MessageBus $bus): void
{
    $bus->send(new Register());
}
```

Transport interfaces:
- `ProducerTransport`
- `ConsumerTransport`

## Publish/Subscribe

Publisher publishes Events, Subscriber subscribes to Publisher.

```php
final readonly class Registered {}

function recommended(): Result
{
    return publish(new Registered());
}

function lowLevel(Context $context): void
{
    $context->publish(new Registered());
}

function entrypoint(MessageBus $bus): void
{
    $bus->publish(new Registered());
}
```

Transport interfaces:
- `PublisherTransport`
- `SubscriberTransport`

## Client/Service

Client invokes Methods on Service.

```php
/**
 * @implements Method<bool>
 */
final readonly class IsRegistered implements Method {}

function recommended(Invoke $invoke): bool
{
    return $invoke(new IsRegistered());
}

function lowLevel(Context $context): bool
{
    return $context->invoke(new IsRegistered());
}

function entrypoint(MessageBus $bus): bool
{
    return $bus->invoke(new IsRegistered());
}
```

Transport interfaces:
- `ClientTransport`
- `ServerTransport`
