<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\OpenAPI;

use cebe\openapi\spec\PathItem;
use JsonSerializable;

final class MakesPathItem implements JsonSerializable
{
    public function __construct(
        private readonly ?MakesOperation $get = null,
    ) {}

    public function asCebeObject(): PathItem
    {
        return new PathItem($this->jsonSerialize());
    }

    public function asJson(): string
    {
        return json_encode($this);
    }

    public function jsonSerialize(): mixed
    {
        return array_filter(
            [
            'get' => $this->get?->jsonSerialize() ?? '',
            ],
            fn($o) => $o !== '',
        );
    }

}
