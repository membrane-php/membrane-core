<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\OpenAPI;

use Membrane\OpenAPIReader\ValueObject\Partial;
use Membrane\OpenAPIReader\ValueObject\Valid\Identifier;
use Membrane\OpenAPIReader\ValueObject\Valid\V30;

final class MakesPathItem implements \JsonSerializable
{
    public function __construct(
        private readonly ?MakesOperation $get = null,
    ) {
    }

    public function asCebeObject(): V30\PathItem
    {
        $getData = $this->get->jsonSerialize();

        return V30\PathItem::fromPartial(
            new Identifier('test'),
            [],
            new Partial\PathItem(
                path: '/path',
            )
        );
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
