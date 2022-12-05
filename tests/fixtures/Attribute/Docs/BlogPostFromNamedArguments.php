<?php

declare(strict_types=1);

namespace Membrane\Fixtures\Attribute\Docs;

use Membrane\Attribute\FilterOrValidator;
use Membrane\Attribute\Placement;
use Membrane\Attribute\SetFilterOrValidator;
use Membrane\Attribute\Subtype;
use Membrane\Filter\CreateObject\WithNamedArguments;
use Membrane\Filter\Type\ToString;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\String\Length;
use Membrane\Validator\String\Regex;
use Membrane\Validator\Utility\AllOf;

#[SetFilterOrValidator(new RequiredFields('title', 'body'), Placement::BEFORE)]
#[SetFilterOrValidator(new WithNamedArguments(BlogPostFromNamedArguments::class), Placement::AFTER)]
class BlogPostFromNamedArguments
{
    public function __construct(
        #[FilterOrValidator(new ToString())]
        #[FilterOrValidator(new AllOf(new Length(5, 50), new Regex('#^([A-Z][a-z]*\s){0,9}([A-Z][a-z]*)$#')))]
        public string $title,
        #[FilterOrValidator(new ToString())]
        public string $body,
        #[SetFilterOrValidator(new Count(0, 5), Placement::BEFORE)]
        #[FilterOrValidator(new ToString())]
        #[Subtype('string')]
        public array $tags,
    ) {
    }
}
