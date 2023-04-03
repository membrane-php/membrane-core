<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\ExtractPathParameters;

interface ExtractsPathParameters
{
    /** @return array<string, string> */
    public function getPathParams(string $requestPath): array;

    public function __toPHP(): string;
}
