<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Filter\FormatStyle;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

final class PipeDelimited implements Filter
{
    public function __toString(): string
    {
        return 'format pipeDelimited style value';
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
                    new Message('PipeDelimited Filter expects string, %s given', [gettype($value)])
                )
            );
        }

        $nameRemoved = preg_replace('#^.+[^=]=#', '', $value);
        assert(is_string($nameRemoved));

        return Result::noResult(explode('|', $nameRemoved));
    }
}
