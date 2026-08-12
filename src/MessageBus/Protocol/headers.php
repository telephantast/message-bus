<?php

declare(strict_types=1);

namespace Thesis\MessageBus\Protocol;

use Thesis\Headers\IntHeader;
use Thesis\Headers\NonEmptyStringHeader;
use Thesis\Headers\StringHeader;
use Thesis\Headers\TimeHeader;
use Thesis\MessageBus\Protocol\Internal\CorrelationIdHeader;

/** @api */
const MESSAGE_TYPE = new NonEmptyStringHeader('thesis-message-type');
/** @api */
const CONTENT_TYPE = new NonEmptyStringHeader('thesis-content-type');
/** @api */
const CONTENT_ENCODING = new NonEmptyStringHeader('thesis-content-encoding');

/** @api */
const MESSAGE_ID = new NonEmptyStringHeader('thesis-message-id');
/** @api */
const CONVERSATION_ID = new NonEmptyStringHeader('thesis-conversation-id');
/** @api */
const CAUSE_ID = new NonEmptyStringHeader('thesis-cause-id');
/** @api */
const CORRELATION_ID = new CorrelationIdHeader();
/** @api */
const RAW_CORRELATION_ID = new NonEmptyStringHeader(CorrelationIdHeader::NAME);

/** @api */
const ORIGIN_ENDPOINT = new NonEmptyStringHeader('thesis-origin-endpoint');
/** @api */
const REPLY_TO_ENDPOINT = new NonEmptyStringHeader('thesis-reply-to-endpoint');

/** @api */
const CREATED_AT = new TimeHeader('thesis-created-at');
/** @api */
const EXPIRES_AT = new TimeHeader('thesis-expires-at');

/** @api */
const FIRST_FAILED_AT = new TimeHeader('thesis-first-failed-at');
/** @api */
\define(__NAMESPACE__ . '\RETRY_COUNT', IntHeader::nonNegative('thesis-retry-count'));
/** @api */
const FAILURE_ENDPOINT = new NonEmptyStringHeader('thesis-failure-endpoint');
/** @api */
const ERROR_CLASS = new NonEmptyStringHeader('thesis-error-class');
/** @api */
const ERROR_FILE = new StringHeader('thesis-error-file');
/** @api */
const ERROR_LINE = new IntHeader('thesis-error-line');
