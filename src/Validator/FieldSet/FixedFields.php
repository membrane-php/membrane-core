<?php

declare(strict_types=1);

namespace Membrane\Validator\FieldSet;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class FixedFields implements Validator
{
    /** @var string[] */
    private readonly array $fields;

    public function __construct(string ...$fields)
    {
        $this->fields = $fields;
    }

    public function validate(mixed $value): Result
    {
        if (!is_array($value)) {
            $message = new Message('FixedFields Validator requires an array, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        $messages = [];
        foreach ($value as $key => $item) {
            if (!in_array($key, $this->fields)) {
                $messages[] = new Message('%s is not a fixed field', [$key]);
            }
        }

        if (!empty($messages)) {
            return new Result($value, Result::INVALID, new MessageSet(null, ...$messages));
        }

        return new Result($value, Result::VALID);
    }
}
