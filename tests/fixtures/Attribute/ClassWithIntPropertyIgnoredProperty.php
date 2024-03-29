<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\Attribute;

use Membrane\Attribute\Ignored;

class ClassWithIntPropertyIgnoredProperty
{
    public int $integerProperty;
    #[Ignored]
    public string $ignoredProperty;
}
