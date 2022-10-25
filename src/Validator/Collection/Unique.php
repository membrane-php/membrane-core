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

        $array = $value;
        while (count($array) !== 0) {
            if (in_array(array_pop($array), $array, true)) {
                return Result::invalid(
                    $value,
                    new MessageSet(null, new Message('Collection contains duplicate values', []))
                );
            }
        }

        return Result::valid($value);
    }
}
