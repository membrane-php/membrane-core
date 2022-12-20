<?php

declare(strict_types=1);

namespace Membrane\Exception;

use RuntimeException;

class InvalidProcessorArguments extends RuntimeException
{
    public const TOO_MANY_PROCESSORS = 0;
    public const NOT_ENOUGH_PROCESSORS = 1;
    public const TOO_MANY_BEFORESETS = 2;
    public const TOO_MANY_AFTERSETS = 3;

    public static function multipleProcessorsInCollection(): self
    {
        $message = 'Cannot use more than one Processor on a Collection';
        return new self($message, self::TOO_MANY_PROCESSORS);
    }

    public static function multipleBeforeSetsInFieldSet(): self
    {
        $message = 'Cannot use more than one BeforeSet on a FieldSet';
        return new self($message, self::TOO_MANY_BEFORESETS);
    }

    public static function multipleAfterSetsInFieldSet(): self
    {
        $message = 'Cannot use more than one AfterSet on a FieldSet';
        return new self($message, self::TOO_MANY_AFTERSETS);
    }

    public static function redundantProcessor(string $processorName): self
    {
        $message = sprintf('%s Processor expects at least two Processors', $processorName);
        return new self($message, self::NOT_ENOUGH_PROCESSORS);
    }
}
