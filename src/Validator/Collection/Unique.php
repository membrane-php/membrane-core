<?php

declare(strict_types=1);

namespace Membrane\Validator\Collection;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Unique implements Validator
{
    public function __construct()
    {
    }

    public function validate(mixed $value): Result
    {
        if (!is_array($value)) {
            return Result::invalid(
                $value,
                new MessageSet(null, new Message('Unique Validator requires an array, %s given', [gettype($value)]))
            );
        }

        if (count($value) < 2) {
            return Result::valid($value);
        }

        $items = array_map(static fn($item) => var_export([gettype($item), $item], true), $value);

        if (count($items) !== count(array_unique($items))) {
            return Result::invalid(
                $value,
                new MessageSet(null, new Message('Collection contains duplicate values', []))
            );
        }

        return Result::valid($value);
    }
}
