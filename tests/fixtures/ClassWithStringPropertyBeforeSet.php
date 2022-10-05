<?php

declare(strict_types=1);

namespace Membrane\Fixtures;

use Membrane\Attribute\Placement;
use Membrane\Attribute\SetFilterOrValidator;
use Membrane\Validator\Object\RequiredFields;

#[SetFilterOrValidator(new RequiredFields('property'), Placement::BEFORE)]
class ClassWithStringPropertyBeforeSet
{
    public string $property = 'property';
}
