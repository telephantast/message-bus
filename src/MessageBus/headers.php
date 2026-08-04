<?php

declare(strict_types=1);

namespace Thesis\MessageBus;

use Thesis\Headers\IntHeader;
use Thesis\Headers\NonEmptyStringHeader;
use Thesis\Headers\TimeHeader;

const MESSAGE_TYPE = new NonEmptyStringHeader('thesis-message-type');
const CONTENT_TYPE = new NonEmptyStringHeader('thesis-content-type');
const CONTENT_ENCODING = new NonEmptyStringHeader('thesis-content-encoding');

const MESSAGE_ID = new NonEmptyStringHeader('thesis-message-id');
const CONVERSATION_ID = new NonEmptyStringHeader('thesis-conversation-id');
const CAUSE_ID = new NonEmptyStringHeader('thesis-cause-id');
const CORRELATION_ID = new NonEmptyStringHeader('thesis-correlation-id');

const ORIGIN_ENDPOINT = new NonEmptyStringHeader('thesis-origin-endpoint');
const REPLY_TO_ENDPOINT = new NonEmptyStringHeader('thesis-reply-to-endpoint');

const CREATED_AT = new TimeHeader('thesis-created-at');

\define(__NAMESPACE__ . '\RETRY_COUNT', IntHeader::nonNegative('thesis-retry-count'));
const RETRY_STARTED_AT = new TimeHeader('thesis-retry-started-at');

// todo const expiresAt = new TimeHeader('thesis-expires-at');
