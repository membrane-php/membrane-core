<?php

declare(strict_types=1);

namespace Membrane\Filter\Type;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class ToInt implements Filter
{
    public function filter(mixed $value): Result
    {
        $type = gettype($value);
        $scalar = is_scalar($value);

        if (!$scalar) {
            $message = new Message('ToInt filter only accepts scalar variables, %s is not scalar', [$type]);
            return Result::invalid($value, new MessageSet(null, $message));
        }
        if ($type === 'string' && !is_numeric($value)) {
            $message = new Message('ToInt filter only accepts numeric strings', []);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::noResult((int)$value);
    }
}
