<?php

declare(strict_types=1);

namespace Membrane\Fixtures;

use Membrane\Attribute\FilterOrValidator;
use Membrane\Validator\Type\IsInt;

class ClassWithIntPropertyIsIntValidator
{
    #[FilterOrValidator(new IsInt())]
    public int $integerProperty;
}
