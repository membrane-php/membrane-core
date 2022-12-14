<?php

declare(strict_types=1);

namespace Membrane\Fixtures\Attribute\Docs;

use Membrane\Attribute\FilterOrValidator;
use Membrane\Attribute\Placement;
use Membrane\Attribute\SetFilterOrValidator;
use Membrane\Attribute\Subtype;
use Membrane\Filter\Type\ToString;
use Membrane\Validator\Collection\Count;
use Membrane\Validator\FieldSet\RequiredFields;


#[SetFilterOrValidator(new RequiredFields('title', 'body'), Placement::BEFORE)]
class BlogPostMaxTags
{
    public function __construct(
        #[FilterOrValidator(new ToString())]
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
