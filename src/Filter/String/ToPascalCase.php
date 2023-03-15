<?php

declare(strict_types=1);

namespace Membrane\Filter\String;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class ToPascalCase implements Filter
{
    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('ToPascalCase Filter expects a string value, %s passed instead', [gettype($value)])
                )
            );
        }

        $whiteSpaceSeperatedValue = preg_replace('#[\s\-_]#', ' ', $value);

        assert(is_string($whiteSpaceSeperatedValue));
        $capitilisedValue = ucwords($whiteSpaceSeperatedValue);

        $pascalValue = preg_replace('#\s#', '', $capitilisedValue);

        return Result::noResult($pascalValue);
    }

    public function __toString(): string
    {
        return 'Convert camelCase, kebab-case, snake-case, or plain text with whitespaces into PascalCase';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s()', self::class);
    }
}
