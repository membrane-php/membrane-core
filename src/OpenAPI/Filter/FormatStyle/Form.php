<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Filter\FormatStyle;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

final class Form implements Filter
{
    private const PRIMITIVE_TYPES = [
        'boolean',
        'string',
        'integer',
        'number',
    ];

    public function __construct(
        private readonly string $type,
        private readonly bool $explode,
    ) {
    }

    public function __toString(): string
    {
        return 'format form style value';
    }

    public function __toPHP(): string
    {
        return sprintf(
            'new %s("%s",%s)',
            self::class,
            $this->type,
            $this->explode ? 'true' : 'false'
        );
    }

    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('Form Filter expects string, %s given', [gettype($value)])
                )
            );
        }

        if (in_array($this->type, self::PRIMITIVE_TYPES)) {
            return Result::noResult($this->removeNameFromBeginning($value));
        }

        if ($this->type === 'array') {
            $result = $this->removeNameBeforeEachValue($value);

            return Result::noResult(array_values(array_filter(
                explode(',', $result),
                fn($v) => $v !== '',
            )));
        }

        if ($this->type === 'object') {
            if ($this->explode) {
                $result = [];
                $token = strtok($value, '=&');
                while ($token !== false) {
                    $result[] = $token;
                    $token = strtok('=&');
                }

                return Result::noResult($result);
            } else {
                $result = $this->removeNameFromBeginning($value);
                return Result::noResult(explode(',', $result));
            }
        }

        return Result::noResult($this->removeNameFromBeginning($value));
    }

    private function removeNameFromBeginning(string $value): string
    {
        $result = preg_replace('#^.+=#', '', $value, 1);
        assert(is_string($result));
        return $result;
    }

    private function removeNameBeforeEachValue(string $value): string
    {
        $result = preg_replace('#&?[^&=]+=#', ',', $value);
        assert(is_string($result));
        return $result;
    }
}
