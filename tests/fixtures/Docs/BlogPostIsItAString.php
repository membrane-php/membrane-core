<?php

declare(strict_types=1);

namespace Membrane\Fixtures\Docs;

use Membrane\Attribute\FilterOrValidator;
use Membrane\Attribute\Placement;
use Membrane\Attribute\SetFilterOrValidator;
use Membrane\Attribute\Subtype;
use Membrane\Validator\Object\RequiredFields;
use Membrane\Validator\Type\IsString;

#[SetFilterOrValidator(new RequiredFields('title', 'body', 'tags'), Placement::BEFORE)]
class BlogPostIsItAString
{
    public function __construct(
        #[FilterOrValidator(new IsString())]
        public string $title,
        #[FilterOrValidator(new IsString())]
        public string $body,
        #[FilterOrValidator(new IsString())]
        #[Subtype('string')]
        public array $tags,
    ) {
    }
}
