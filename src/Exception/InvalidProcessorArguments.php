<?php

declare(strict_types=1);

namespace Membrane\Exception;

use RuntimeException;

class InvalidProcessorArguments extends RuntimeException
{
    public static function multipleProcessorsInCollection(): self
    {
        $message = 'Cannot use more than one Processor on a Collection';
        return new self($message);
    }

    public static function multipleBeforeSetsInFieldSet(): self
    {
        $message = 'Cannot use more than one BeforeSet on a FieldSet';
        return new self($message);
    }

    public static function multipleAfterSetsInFieldSet(): self
    {
        $message = 'Cannot use more than one AfterSet on a FieldSet';
        return new self($message);
    }
}
