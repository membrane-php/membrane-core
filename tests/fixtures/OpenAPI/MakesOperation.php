<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\OpenAPI;

use JsonSerializable;

final class MakesOperation implements JsonSerializable
{
    public function __construct(
        private readonly array $parameters = [],
    ) {
    }

    public static function withHeader(
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

    public function asJson(): string
    {
        return json_encode($this);
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
