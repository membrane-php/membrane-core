<?php

declare(strict_types=1);

namespace Membrane\Filter\String;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class ToKebabCase implements Filter
{
    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            return Result::invalid($value, new MessageSet(null, new Message(
                'Expected string value, received %s',
                [gettype($value)],
            )));
        }

        $result = preg_replace(['#[\s\-.,_/]+#', '#[^[:alnum:]-]#'], ['-'], $value);
        assert(is_string($result));

        $result = mb_strtolower($result);

        return Result::noResult($result);
    }

    public function __toString(): string
    {
        return 'Convert text to kebab-case';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s()', self::class);
    }
}
