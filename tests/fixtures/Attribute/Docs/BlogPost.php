<?php

declare(strict_types=1);

namespace Membrane\Fixtures\Attribute\Docs;

use Membrane\Attribute\Subtype;

class BlogPost
{
    public function __construct(
        public string $title,
        public string $body,
        #[Subtype('string')]
        public array $tags = [],
    ) {
    }
}
