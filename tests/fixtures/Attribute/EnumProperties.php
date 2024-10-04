<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\Attribute;

use Membrane\Tests\Fixtures\Enum\IntBackedDummy;
use Membrane\Tests\Fixtures\Enum\StringBackedDummy;

class EnumProperties
{
    public StringBackedDummy $stringBackedEnum;

    public IntBackedDummy $intBackedEnum;
}
