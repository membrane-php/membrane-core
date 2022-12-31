<?php

declare(strict_types=1);

namespace Membrane\Exception;

use RuntimeException;

/*
 * This exception occurs if invalid arguments have been passed to a processor.
 * This error will never occur through the Membrane class or through a Builder.
 * This will only occur by developer error when attempting to use a processor manually.
 * This may be due to one of the following reasons:
 * 1: Too many of one type of processor have been passed
 * 2: The processor being used serves no purpose
 */

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
