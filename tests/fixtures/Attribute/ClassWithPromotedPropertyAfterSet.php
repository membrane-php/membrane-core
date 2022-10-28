<?php

declare(strict_types=1);

namespace Membrane\Fixtures\Attribute;

use Membrane\Attribute\FilterOrValidator;
use Membrane\Attribute\Placement;
use Membrane\Attribute\SetFilterOrValidator;
use Membrane\Filter\CreateObject\WithNamedArguments;
use Membrane\Validator\Type\IsInt;

#[SetFilterOrValidator(new WithNamedArguments(ClassWithPromotedPropertyAfterSet::class), Placement::AFTER)]
class ClassWithPromotedPropertyAfterSet
{
 public function __construct(
     #[FilterOrValidator(new IsInt())]
     public int $promotedProperty)
 {
 }
}
