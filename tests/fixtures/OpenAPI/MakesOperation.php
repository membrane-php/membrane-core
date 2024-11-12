<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\OpenAPI;

use JsonSerializable;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Style;

final class MakesOperation implements JsonSerializable
{
    public function __construct(
        private readonly array $parameters = [],
    ) {
    }

    public static function withPathParameter(
        string $name,
        Style $style,
        bool $explode,
        bool|array $schema,
    ): self {
        return new self([[
            'name' => $name,
            'in' => 'path',
            'required' => true,
            'style' => $style->value,
            'explode' => $explode,
            'schema' => $schema,
        ]]);
    }

    public static function withQueryParameter(
        string $name,
        bool $required,
        string $style,
        bool $explode,
        bool|array $schema,
    ): self {
        return new self([[
            'name' => $name,
            'in' => 'query',
            'required' => $required,
            'style' => $style,
            'explode' => $explode,
            'schema' => $schema,
        ]]);
    }

    public static function withHeaderParameter(
        string $name,
        bool $required,
        bool $explode,
        bool|array $schema,
    ): self {
        return new self([[
            'name' => $name,
            'in' => 'header',
            'required' => $required,
            'style' => 'simple',
            'explode' => $explode,
            'schema' => $schema,
        ]]);
    }

    public function jsonSerialize(): mixed
    {
        $result = [
            'operationId' => 'test',
            'responses' => ['200' => ['description' => 'Success']],
        ];

        if (!empty($this->parameters)) {
            $result['parameters'] = $this->parameters;
        }

        return $result;
    }
}
