<?php

declare(strict_types=1);

namespace Membrane\Fixtures;

use Membrane\Attribute\Subtype;

class ClassWithNestedCollection
{
    #[Subtype('array')]
    public array $arrayOfArrays;
}
