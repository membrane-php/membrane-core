<?php

declare(strict_types=1);

namespace Membrane\Fixtures\Attribute;

use Membrane\Attribute\Ignored;

class EmptyClassWithIgnoredProperty
{
    #[Ignored]
    public string $ignored;
}
