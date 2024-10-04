<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\Attribute;

use Membrane\Attribute\FilterOrValidator;
use Membrane\Filter\Type\ToBackedEnum;
use Membrane\Tests\Fixtures\Enum\IntBackedDummy;
use Membrane\Tests\Fixtures\Enum\StringBackedDummy;

class EnumPropertiesWithAttributes
{
    #[FilterOrValidator(new ToBackedEnum(StringBackedDummy::class))]
    public StringBackedDummy $stringBackedEnum;

    #[FilterOrValidator(new ToBackedEnum(IntBackedDummy::class))]
    public IntBackedDummy $intBackedEnum;
}
