# Thesis MessageBus

[![PHP Version Requirement](https://img.shields.io/packagist/dependency-v/thesis/message-bus/php)](https://packagist.org/packages/thesis/message-bus)
[![GitHub Release](https://img.shields.io/github/v/release/thesis-php/message-bus)](https://github.com/thesis-php/message-bus/releases)
[![Code Coverage](https://codecov.io/gh/thesis-php/message-bus/branch/0.3.x/graph/badge.svg)](https://codecov.io/gh/thesis-php/message-bus/tree/0.3.x)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fthesis-php%2Fmessage-bus%2F0.3.x)](https://dashboard.stryker-mutator.io/reports/github.com/thesis-php/message-bus/0.3.x)

## Installation

```shell
composer require thesis/message-bus
```

## Dispatch a message from endpoint

```php
$endpoint->dispatch(new Ping('Hi there!'));
```

```mermaid
---
config:
  theme: redux
---
flowchart TD
    dispatch --> message
    message --> outgoing_envelope_processors --> has_sync_handlers
    has_sync_handlers -- No --> publish_message --> END
    has_sync_handlers -- Yes --> begin --> handle --> is_event
    is_event -- Yes --> add_to_outbox --> save_outbox
    is_event -- No --> save_outbox
    save_outbox --> commit --> publish_outbox --> save_empty_outbox --> END
    dispatch["Dispatch from endpoint"]@{shape: terminal}
    message["Message"]@{shape: in-out}
    outgoing_envelope_processors["Process with OutgoingEnvelopeProcessors"]
    has_sync_handlers["Has sync handlers?"]@{shape: decision}
    publish_message["Publish message"]
    begin["Begin transaction"]
    handle["Handle with sync handlers"]
    is_event["Is Event?"]@{shape: diam}
    add_to_outbox["Add to outbox"]
    save_outbox["Save outbox"]
    commit["Commit transaction"]
    publish_outbox["Publish outbox"]
    save_empty_outbox["Save empty outbox"]
    END["End"]@{shape: terminal}
```

## Consume message

```php
$endpoint->dispatch(new Ping('Hi there!'));
```

```mermaid
---
config:
  theme: redux
---
flowchart TD
    consume --> message
    message --> find_outbox
    find_outbox --> outbox_exists -- Yes --> is_outbox_empty
    outbox_exists -- No --> begin --> handle --> save_outbox --> commit --> is_outbox_empty
    is_outbox_empty -- Yes --> END
    is_outbox_empty -- No --> publish_outbox --> save_empty_outbox --> END
    consume["Consume"]@{shape: terminal}
    message["Message"]@{shape: in-out}
    find_outbox["Find outbox for consumed message"]
    outbox_exists["Outbox exists?"]@{shape: decision}
    begin["Begin transaction"]
    handle["Handle with async handlers"]
    save_outbox["Save outbox"]
    commit["Commit transaction"]
    is_outbox_empty["Is outbox empty?"]
    publish_outbox["Publish outbox"]
    save_empty_outbox["Save empty outbox"]
    END["End"]@{shape: terminal}
```
