<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Filter\FormatStyle;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

final class DeepObject implements Filter
{
    public function __toString(): string
    {
        return 'format deepObject style value';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s()', self::class);
    }

    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('DeepObject Filter expects string, %s given', [gettype($value)])
                )
            );
        }

        $result = [];

        $pairs = explode('&', $value);
        foreach ($pairs as $pair) {
            $nameRemoved = preg_replace('#^[^\[\]=]+#', '', $pair);
            assert(is_string($nameRemoved));

            $bracesRemoved = str_replace(['[', ']'], '', $nameRemoved);
            assert(is_string($bracesRemoved));

            $splitPair = explode('=', $bracesRemoved);

            $result = array_merge($result, $splitPair);
        }

        return Result::noResult($result);
    }
}
