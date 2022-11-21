<?php

declare(strict_types=1);

namespace Membrane\Filter\String;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class JsonDecode implements Filter
{
    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('JsonDecode Filter expects a string value, %s passed instead', [gettype($value)])
                )
            );
        }

        $value = json_decode($value, true);

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return Result::valid($value);
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_SYNTAX:
            case JSON_ERROR_INVALID_PROPERTY_NAME:
                $message = 'Syntax error occurred';
                break;
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_UTF8:
            case JSON_ERROR_UTF16:
                $message = 'Character error occurred, possibly incorrectly encoded';
                break;
            default:
                $message = 'An error occurred';
        }

        return Result::invalid($value, new MessageSet(null, new Message($message, [])));
    }
}
