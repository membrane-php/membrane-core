<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\Enum;

enum StringBackedDummy: string
{
    case Hello = 'hello';
    case World = 'world';
}
