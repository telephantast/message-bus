# Thesis MessageBus

[![PHP Version Requirement](https://img.shields.io/packagist/dependency-v/thesis/message-bus/php)](https://packagist.org/packages/thesis/message-bus)
[![GitHub Release](https://img.shields.io/github/v/release/thesisphp/message-bus)](https://github.com/thesisphp/message-bus/releases)
[![Code Coverage](https://codecov.io/gh/thesisphp/message-bus/branch/0.2.x/graph/badge.svg)](https://codecov.io/gh/thesisphp/message-bus/tree/0.2.x)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fthesisphp%2Fmessage-bus%2F0.2.x)](https://dashboard.stryker-mutator.io/reports/github.com/thesisphp/message-bus/0.2.x)

## Installation

```shell
composer require thesis/message-bus
```

## Quick Start

```php
use Thesis\Message\Command;
use Thesis\Message\Event;
use Thesis\Message\Message;
use Thesis\MessageBus\Context;
use Thesis\MessageBus\HandlerRegistry;
use Thesis\MessageBus\MessageBus;

// First, declare a bunch of messages.

final readonly class RegisterUser implements Command
{
    public function __construct(
        public string $nickname,
        public string $firstName,
    ) {}
}

final readonly class UserRegistered implements Event
{
    public function __construct(
        public string $nickname,
    ) {}
}

/**
 * @implements Message<string>
 */
final readonly class GetUserName implements Message
{
    public function __construct(
        public string $nickname,
    ) {}
}

// Then assemble a simple synchronous MessageBus.

$firstNames = [];

$messageBus = new MessageBus(
    handlerRegistry: HandlerRegistry::builder()
        ->addCallableHandler(function (RegisterUser $command, Context $context) use (&$firstNames): void {
            $firstNames[$command->nickname] = $command->firstName;

            $context->dispatch(new UserRegistered($command->nickname));
        })
        ->addCallableHandler(function (UserRegistered $event): void {
            echo "User {$event->nickname} has just registered!\n";
        })
        ->addCallableHandler(function (GetUserName $query) use (&$firstNames): string {
            return $firstNames[$query->nickname];
        })
        ->build(),
);

// Give it a try!

$messageBus->dispatch(new RegisterUser('vudaltsov', 'Valentin'));

$firstName = $messageBus->dispatch(new GetUserName('vudaltsov'));

echo "Get acquainted with {$firstName}!\n";
```
