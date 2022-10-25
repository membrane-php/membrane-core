<?php

declare(strict_types=1);

namespace Membrane\Fixtures\Attribute\Docs;

use Membrane\Attribute\Placement;
use Membrane\Attribute\SetFilterOrValidator;
use Membrane\Attribute\Subtype;
use Membrane\Validator\FieldSet\RequiredFields;

#[SetFilterOrValidator(new RequiredFields('title', 'body'), Placement::BEFORE)]
class BlogPostRequiredFields
{
    public function __construct(
        public string $title,
        public string $body,
        #[Subtype('string')]
        public array $tags = [],
    ) {
    }
}
