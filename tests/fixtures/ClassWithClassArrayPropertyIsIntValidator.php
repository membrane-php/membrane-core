<?php

declare(strict_types=1);

namespace Membrane\Fixtures;

use Membrane\Attribute\FilterOrValidator;
use Membrane\Attribute\Subtype;
use Membrane\Validator\Type\IsInt;

class ClassWithClassArrayPropertyIsIntValidator
{
    #[FilterOrValidator(new IsInt())]
    #[Subtype(ClassWithIntPropertyIsIntValidator::class)]
    public array $arrayOfClasses;
}
