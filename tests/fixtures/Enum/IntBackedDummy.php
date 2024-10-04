<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\Enum;

enum IntBackedDummy: int
{
    case Zero = 0;
    case One = 1;
}
